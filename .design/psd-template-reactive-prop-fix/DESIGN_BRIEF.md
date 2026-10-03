# PSD Template Reactive Prop Fix

## Goal

Allow product cards and status panels to refresh their active PSD template after an upload without Livewire reactive-prop mutation errors.

## Preserved Behavior

- PSD upload events still refresh the active template name.
- Provider and model props remain reactive.
- Decal, Glass, Sticker, and Ornament Etsy workflows remain unchanged.

## Acceptance Criteria

- No component mutates `activePsdTemplateName` while it is declared reactive.
- Uploading a PSD template does not throw `CannotMutateReactivePropException`.
