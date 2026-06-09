<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use RuntimeException;
use Throwable;

class YtrendsSearch extends Component
{
    private const MCP_URL = 'https://mcp.trends.ytuong.ai/mcp';

    private const CACHE_MINUTES = 10;

    public string $keyword = '';

    public ?string $searchedKeyword = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = null;

    public ?string $error = null;

    /**
     * Analyze an Etsy keyword through the YTrends MCP research_keyword tool.
     */
    public function search(): void
    {
        $this->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $this->error = null;
        $this->data = null;

        $keyword = trim($this->keyword);
        $this->searchedKeyword = $keyword;
        $cacheKey = 'ytrends_keyword_detailed_'.md5(strtolower($keyword));

        try {
            $this->data = Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), fn (): array => $this->callResearchKeyword($keyword));
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
        }
    }

    /**
     * Render the YTrends keyword search widget.
     */
    public function render(): View
    {
        return view('livewire.ytrends-search');
    }

    /**
     * Call the YTrends MCP research keyword tool with a cached MCP session.
     *
     * @return array<string, mixed>
     */
    private function callResearchKeyword(string $keyword): array
    {
        $sessionId = Cache::remember('ytrends_mcp_session_id', now()->addMinutes(5), fn (): string => $this->initializeMcpSession());

        $response = Http::timeout(60)
            ->withHeaders($this->mcpHeaders($sessionId))
            ->post(self::MCP_URL, [
                'jsonrpc' => '2.0',
                'id' => uniqid('ytrends_', true),
                'method' => 'tools/call',
                'params' => [
                    'name' => 'ytrends_research_keyword',
                    'arguments' => [
                        'keyword' => $keyword,
                        'response_format' => 'detailed',
                    ],
                ],
            ])
            ->throw();

        return $this->decodeMcpResponse($response);
    }

    /**
     * Initialize a streamable HTTP MCP session and return its session id.
     */
    private function initializeMcpSession(): string
    {
        $response = Http::timeout(20)
            ->withHeaders($this->mcpHeaders())
            ->post(self::MCP_URL, [
                'jsonrpc' => '2.0',
                'id' => uniqid('ytrends_init_', true),
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => new \stdClass(),
                    'clientInfo' => [
                        'name' => 'xlap-ytrends',
                        'version' => '1.0.0',
                    ],
                ],
            ])
            ->throw();

        $sessionId = $response->header('mcp-session-id');

        if (! is_string($sessionId) || trim($sessionId) === '') {
            throw new RuntimeException('YTrends MCP did not return a session id.');
        }

        return $sessionId;
    }

    /**
     * Build headers required by the YTrends streamable HTTP MCP endpoint.
     *
     * @return array<string, string>
     */
    private function mcpHeaders(?string $sessionId = null): array
    {
        $headers = [
            'Accept' => 'application/json, text/event-stream',
        ];

        if ($sessionId !== null) {
            $headers['Mcp-Session-Id'] = $sessionId;
        }

        return $headers;
    }

    /**
     * Decode either JSON or text/event-stream MCP responses.
     *
     * @return array<string, mixed>
     */
    private function decodeMcpResponse(Response $response): array
    {
        $payload = trim($response->body());
        $dataLines = [];

        foreach (preg_split('/\R/', $payload) ?: [] as $line) {
            if (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(substr($line, 5));
            }
        }

        if ($dataLines !== []) {
            $payload = implode("\n", $dataLines);
        }

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('YTrends MCP returned an invalid response.');
        }

        if (($decoded['result']['isError'] ?? false) === true) {
            throw new RuntimeException($decoded['result']['content'][0]['text'] ?? 'YTrends MCP returned an error.');
        }

        $toolText = $decoded['result']['content'][0]['text'] ?? null;

        if (is_string($toolText)) {
            $toolDecoded = json_decode($toolText, true);

            if (is_array($toolDecoded)) {
                return $toolDecoded;
            }
        }

        return $decoded;
    }
}
