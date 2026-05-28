# Vertex AI Gemini Integration

This document records what Offorest needs to send to Vertex AI Gemini and how the database stores the workflow.

## API endpoint

Use `generateContent` for Gemini multimodal image workflows.

```text
POST https://aiplatform.googleapis.com/v1/{model}:generateContent
```

For express mode, `{model}` is the fully qualified publisher model name:

```text
publishers/google/models/gemini-2.5-flash-image
```

For regional Vertex AI calls, use the project/location publisher model path used by Google Cloud IAM:

```text
projects/{project}/locations/{location}/publishers/google/models/{model}
```

## Minimum request body

```json
{
  "contents": [
    {
      "role": "user",
      "parts": [
        {
          "text": "Generate a clean product mockup from the uploaded design."
        }
      ]
    }
  ],
  "generationConfig": {
    "responseModalities": ["TEXT", "IMAGE"]
  }
}
```

## Image input

For small images, send image bytes inline as base64:

```json
{
  "role": "user",
  "parts": [
    {
      "text": "Use this uploaded design as the exact artwork."
    },
    {
      "inlineData": {
        "mimeType": "image/png",
        "data": "BASE64_IMAGE_BYTES"
      }
    }
  ]
}
```

For larger files, prefer `fileData` with a Cloud Storage URI or public HTTP URL:

```json
{
  "fileData": {
    "mimeType": "image/png",
    "fileUri": "gs://bucket/path/source.png"
  }
}
```

Offorest should not persist base64 image data in `vertex_ai_requests.request_payload`. Store image files in `assets` and only store sanitized metadata in the API audit row.

## Fields Offorest needs from the user/admin

Credential/config fields:

- Google Cloud project ID
- Location, usually `global` or a supported Vertex AI region
- Model name, for example `gemini-2.5-flash-image`
- Service account JSON or another server-side Google auth method
- Optional key issued/expiry dates for admin visibility

Per job fields:

- Prompt text
- Optional negative prompt or system instruction
- Input asset IDs and each input role, such as `source`, `mask`, or `reference`
- Generation options, such as response modalities, temperature, candidate count, aspect ratio, or output count

Response handling:

- Store generated image files as `assets`
- Link them through `ai_job_outputs`
- Store text/error/usage metadata in `vertex_ai_requests`

## Tables

`vertex_ai_credentials` stores per-user Vertex AI configuration and encrypted credential JSON.

`vertex_ai_requests` stores sanitized API call audit data for a user/job/credential. It is intentionally separate from `ai_jobs` so retries and multiple model calls can be tracked under one job.

## Model relations

- `User hasMany VertexAiCredential`
- `User hasMany VertexAiRequest`
- `VertexAiCredential hasMany VertexAiRequest`
- `AiJob hasMany VertexAiRequest`
- `VertexAiRequest belongsTo User`
- `VertexAiRequest belongsTo AiJob`
- `VertexAiRequest belongsTo VertexAiCredential`
