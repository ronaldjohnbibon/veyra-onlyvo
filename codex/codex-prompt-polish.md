Implement dynamic template content fields based on the selected website template.

Current behavior:

- The template renderer is already dynamic through `resources/js/modules/templates/components/registry.ts`.
- `TemplatePreview.vue` renders the selected Vue template by `website_type.slug` and `template_key`.
- But `TemplateSelectionPage.vue` currently uses one static form for every template: business name, logo, contact info, social links, colors, etc.

Goal:
Each catalog template should define its own editable content fields. When the user selects a template, the form should change based on that template’s schema. The saved template should store the user’s answers and pass them into the selected Vue template preview/public page.

Rules:

- Read C:\Users\lenovo\Desktop\projects\veyra-onlyvo\codex\codex.md.
- Follow the existing project structure, coding style, and patterns.
- When changing frontend code, also check and update related backend code if needed.
- When changing backend code, also check and update related frontend code if needed.
- Use existing shadcn UI components for fields, buttons, dialogs, tables, cards, and other UI elements when suitable.
- Keep the code simple, direct, and human-coded.
- Avoid unnecessary abstraction, over engineering, or unrelated refactoring.
- Make sure the final code is working.
- Do not create tests.
- Add short, direct comments for important logic and functions. Each comment should be simple and explain what the code does in one short sentence.

Requirements:

1. Add JSON fields to the backend:
   - Add `field_schema` JSON nullable column to `template_catalog_items`.
   - Add `default_content` JSON nullable column to `template_catalog_items`.
   - Add `content` JSON nullable column to `templates`.

2. Update backend models/resources/requests:
   - `TemplateCatalogItem` should fill/cast `field_schema` and `default_content`.
   - `Template` should fill/cast `content`.
   - `TemplateCatalogItemRequest` should validate schema/content as nullable arrays.
   - `TemplateRequest` should validate `content` as nullable array.
   - `TemplateCatalogItemResource` and `TemplateResource` should return these new fields.

3. Update TypeScript types:
   - Add reusable dynamic field types in `resources/js/types/templates.d.ts`.
   - Support fields like:
     - text
     - textarea
     - url
     - email
     - phone
     - image
     - color
     - number
     - boolean
     - select
     - repeater
   - Add `field_schema?: TemplateFieldSchema[]`
   - Add `default_content?: Record<string, unknown>`
   - Add `content: Record<string, unknown>` to template payload/record.

4. Update admin template maintenance:
   - In `AdminTemplateMaintenancePage.vue`, allow admins to edit `field_schema` and `default_content` as JSON textareas for now.
   - Validate JSON before save and show a clear error if invalid.
   - Keep this simple; no visual schema builder yet.

5. Update tenant template selection:
   - In `TemplateSelectionPage.vue`, when a catalog template is selected:
     - Load its `field_schema`.
     - Merge `default_content` with any existing saved `template.content`.
     - Render fields dynamically from the schema.
   - Keep the existing global fields/styles where they still make sense.
   - Replace hardcoded business-specific fields with dynamic content fields where possible.
   - Save the dynamic values into `form.content`.

6. Build a reusable dynamic form component:
   - Create something like `resources/js/modules/templates/components/DynamicTemplateFields.vue`.
   - It should accept:
     - `schema`
     - `modelValue`
   - Emit updated content.
   - Render appropriate shadcn/ui inputs based on field type.
   - Support nested repeater fields for arrays of objects.

7. Update template rendering:
   - Existing Vue templates should read from `template.content` first.
   - Use sensible fallbacks so existing templates do not break.
   - Example:
     - `template.content.hero_title ?? "Default hero title"`
     - `template.content.services ?? [...]`

8. Seed example schemas:
   - Add useful `field_schema` and `default_content` for the existing templates:
     - business website
     - portfolio website
     - restaurant website
   - Make each one meaningfully different:
     - Business: hero, CTA, services, stats, proof text.
     - Portfolio: headline, bio, projects, skills, contact CTA.
     - Restaurant: hero, menu items, hours, location, reservation link.

9. Verification:
   - Run formatting/linting if available.
   - Run relevant tests if available.
   - Manually verify that selecting different templates changes the visible form fields.
   - Verify preview updates from dynamic content.
   - Verify saved templates reload with their dynamic content intact.

Important constraints:

- Follow existing Laravel/Vue/Pinia patterns in the repo.
- Keep changes scoped to the Templates module.
- Do not remove the existing template registry/rendering approach.
- Do not build a complex drag-and-drop builder.
- Use JSON schema-style config for now, simple and maintainable.
