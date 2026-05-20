- The Template module allows users to create a business website by selecting predefined website sections, choosing a preset design for each selected section, and applying global style options like font and color. The layout and component structure are fixed by the selected design and cannot be manually customized.

Module Name: Templates
Feature Name: Template Assembler
Purpose: Assemble a business website using predefined sections and designs.

# MISSING

- Template Name
- Selected Sections
- Section Design
- Section Order
- Font
- Primary Color
- Secondary Color
- Logo
- Business Name
- Contact Info
- Social Links
- Preview
- Publish Status

# You may also need content fields

Hero Section

- Selected Design: Hero Design 1
- Title
- Subtitle
- Image

# What to improve

Add section enable/disable, because not all businesses need every part.

- Header - enabled
- Hero - enabled
- About - enabled
- Services - enabled
- Products - disabled
- Portfolio - disabled
- FAQ - enabled
- Footer - enabled

Add section ordering, even if the section design is fixed.
Add preview images for each design so the user can choose easily.

Add global style only, not per-element style.

- Font Family
- Primary Color
- Secondary Color
- Background Color
- Text Color

# Better database idea

You can keep it simple:

- templates
- template_sections
- template_section_designs
- template_styles

templates

- id
- name
- business_name
- logo
- font_family
- primary_color
- secondary_color
- status
- created_at
- updated_at

template_sections

- id
- template_id
- section_type
- design_key
- sort_order
- is_enabled
- content_json
- created_at
- updated_at

template_section_designs

- id
- section_type
- name
- design_key
- preview_image
- is_active
- created_at
- updated_at
