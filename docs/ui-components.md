# UI Components

Reusable Blade components live in `resources/views/components`.

## Button

Use `x-ui.button` for repeated buttons instead of writing Tailwind classes manually.

```blade
<x-ui.button color="blue">
    Save
</x-ui.button>

<x-ui.button color="red" variant="outline" type="button">
    Delete
</x-ui.button>

<x-ui.button color="cyan" size="lg" full>
    Create user
</x-ui.button>

<x-ui.button href="{{ route('dashboard') }}" color="slate" variant="soft">
    Go dashboard
</x-ui.button>
```

Props:

- `color`: `default`, `blue`, `dark`, `light`, `green`, `red`, `yellow`, `purple`, `cyan`, `emerald`, `orange`, `slate`.
- `variant`: `solid`, `outline`, `soft`, `ghost`.
- `size`: `xs`, `sm`, `md`, `lg`, `xl`.
- `pill`: boolean, makes the button fully rounded.
- `full`: boolean, makes the button full width.
- `href`: renders an anchor styled as a button.
- `loading`: shows a spinner before the label.

Existing `x-primary-button`, `x-secondary-button`, and `x-danger-button` now wrap `x-ui.button`, so older auth/profile screens keep working.

## Short Aliases

Use these short components for common UI:

```blade
<x-button color="cyan">Save</x-button>
<x-button color="red" variant="outline" type="button">Delete</x-button>

<x-label for="keyword" required>Keyword</x-label>
<x-input id="keyword" wire:model="keyword" class="block w-full" />

<x-textarea wire:model="prompt" class="block w-full" rows="5" />

<x-select wire:model="product_id" class="block w-full">
    <option value="">Choose product</option>
</x-select>

<x-checkbox wire:model="is_active" label="Active" />

<x-card>
    Card content
</x-card>

<x-badge color="cyan">Sticker</x-badge>

<x-alert type="success">
    Saved successfully.
</x-alert>

<x-form-field label="Image link" :error="$errors->first('imageLink')">
    <x-input wire:model="imageLink" class="block w-full" />
</x-form-field>
```

Root components added:

- `x-button`: short alias for `x-ui.button`.
- `x-input`: base input.
- `x-label`: field label.
- `x-textarea`: base textarea.
- `x-select`: base select.
- `x-checkbox`: checkbox with optional label.
- `x-card`: white bordered card.
- `x-badge`: status label.
- `x-alert`: info/success/warning/danger message.
- `x-form-field`: wraps label, input slot, hint, and error.
