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

- Decode generated image bytes from the Vertex inline response.
- If the caller explicitly requests it and `OFFOREST_REMOVE_VERTEX_BACKGROUND=true`, run background removal before writing the file.
- Store the final generated image files as `assets`
- Link them through `ai_job_outputs`
- Store text/error/usage metadata in `vertex_ai_requests`

## Background removal before storage

The current save flow is:

```text
Vertex inline image base64
 -> decode bytes
 -> optional caller-scoped TransformersPHP background removal
 -> normalize output PNG / 300 PPI
 -> Storage::disk('public')->put(...)
```

Config:

```env
OFFOREST_REMOVE_VERTEX_BACKGROUND=false
OFFOREST_BACKGROUND_REMOVAL_ENGINE=magic_eraser
OFFOREST_BACKGROUND_REMOVAL_MODEL=briaai/RMBG-1.4
OFFOREST_BACKGROUND_REMOVAL_IMAGE_DRIVER=GD
OFFOREST_BACKGROUND_REMOVAL_CLEAN_ALPHA=true
OFFOREST_BACKGROUND_REMOVAL_ALPHA_MIN_OPACITY=45
OFFOREST_BACKGROUND_REMOVAL_MIN_COMPONENT_AREA=180
OFFOREST_BACKGROUND_REMOVAL_EDGE_MARGIN_RATIO=0.015
OFFOREST_BACKGROUND_REMOVAL_FOREGROUND_GAP_RATIO=0.08
OFFOREST_BACKGROUND_REMOVAL_EDGE_FLOOD_CLEAN=true
OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_TOLERANCE=58
OFFOREST_BACKGROUND_REMOVAL_EDGE_FLOOD_MIN_OPACITY=12
OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_SAMPLES=3
OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_BUCKET_SIZE=24
```

`VertexImageGenerator::generate()` does not remove backgrounds globally. The caller must pass
`removeBackground: true`, and the service still checks `OFFOREST_REMOVE_VERTEX_BACKGROUND`.
Sticker currently requests background removal only for the master redesign image.

The default local engine is `magic_eraser`: it runs inside Laravel with PHP/GD, samples
dominant edge colors, flood-fills background-colored regions from the image edges, and
cleans disconnected alpha components. It does not call `rembg` or another shell
background-removal command during image generation.

The optional `transformersphp` engine runs inside Laravel with `codewithkyrian/transformers`,
`AutoModel`, `AutoProcessor`, and the BRIA RMBG-1.4 model.

After the model applies the mask, Offorest cleans the output alpha channel before
storing the PNG. The cleaner first samples dominant edge colors and flood-fills
background-colored regions from the image edges. It then removes weak alpha pixels,
edge-touching alpha components, and disconnected components that are far from the
main foreground. Increase `EDGE_COLOR_TOLERANCE` or `MIN_COMPONENT_AREA` if old
background texture remains; lower them if intentional sticker details disappear.

Runtime requirements:

```bash
composer require codewithkyrian/transformers
php vendor/bin/transformers download briaai/RMBG-1.4
```

PHP must have FFI enabled for CLI and the web runtime:

```ini
extension=ffi
ffi.enable=true
```

The model cache is stored in `storage/app/transformers-cache` and must not be committed.

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
