# XLAP shadcn/ui Trial

Isolated React + Vite + shadcn/ui trial for XLAP. It does not replace the existing Laravel, Blade, or Livewire frontend.

## Run locally

```bash
cd tools/shadcn-demo
npm install
npm run dev
```

Open the local URL printed by Vite, usually `http://localhost:5173`.

## Adding components

To add components to your app, run the following command:

```bash
npx shadcn@latest add button
```

This will place the ui components in the `src/components` directory.

## Using components

To use the components in your app, import them as follows:

```tsx
import { Button } from "@/components/ui/button"
```
