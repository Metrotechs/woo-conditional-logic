# WooCommerce Conditional Logic

A powerful WordPress plugin that adds conditional logic to WooCommerce variable products with advanced product options and rules, similar to the functionality described in the [Infinite Apps blog post](https://infiniteapps.net/blog/how-to-create-conditional-product-options?wl=true).

## Features

### Option Sets Management
- Create and manage option sets that can be applied to multiple products
- Support for multiple option types:
  - **Checkbox** - Multiple selections with checkboxes
  - **Radio Button** - Single selection with radio buttons
  - **Dropdown** - Dropdown selection list
  - **Swatch** - Color or image swatches (single selection)
  - **Multi-select Swatch** - Multiple color or image swatches
  - **Button** - Button-style selection
  - **Text Field** - Single line text input
  - **Multi-line Text** - Textarea input
  - **Number Field** - Numeric input
  - **Date Picker** - Date selection with calendar
  - **File Upload** - File upload functionality

### Advanced Option Configuration
- Set price modifiers for option values (fixed or percentage)
- Add descriptions and tooltips for options and values
- Configure required fields
- Set minimum/maximum selections for multi-select options
- Upload images for swatch options
- Set color values for color swatches
- Drag-and-drop reordering of options and values

### Conditional Logic Rules
- Create complex conditional rules with IF-THEN logic
- Multiple condition operators:
  - Equals / Does not equal
  - Contains / Does not contain
  - Is empty / Is not empty
  - Greater than / Less than
- Logical operators (AND/OR) for multiple conditions
- Rule actions:
  - **Hide** - Hide specific options or values
  - **Show** - Show specific options or values
  - **Require** - Make options required dynamically
  - **Modify Price** - Apply price modifications

### Product Integration
- Easy integration with WooCommerce products
- Product-level option set assignment
- Control over original product options (hide, replace, or keep)
- Support for both simple and variable products

### Frontend Experience
- Responsive design that works on all devices
- Real-time conditional logic evaluation
- Dynamic price updates
- Form validation with error messaging
- Smooth animations and transitions

## Installation

1. Upload the plugin files to `/wp-content/plugins/woo-conditional-logic/`
2. Activate the plugin through the WordPress admin
3. Navigate to **WooCommerce > Conditional Logic** to get started

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher

## Usage

### Creating Option Sets

1. Go to **WooCommerce > Conditional Logic**
2. Click **Add New** to create a new option set
3. Enter a name and description for your option set
4. Add options by clicking **Add Option**
5. Configure each option:
   - Set the option name and type
   - Add option values (for select-type options)
   - Set price modifiers
   - Configure colors/images for swatches

### Creating Conditional Rules

1. In the option set editor, scroll to the **Rules** section
2. Click **Add Rule** to create a new rule
3. Configure the condition:
   - Select the trigger option
   - Choose the comparison operator
   - Set the target value
4. Configure the action:
   - Choose what should happen (hide/show/require)
   - Select target options or values
5. Save the rule

### Applying to Products

1. Edit a WooCommerce product
2. Go to the **Conditional Logic** tab
3. Select which option sets to apply
4. Configure display options:
   - Replace existing variations
   - Hide original product options
   - Set position/order
5. Update the product

## Database Schema

The plugin creates the following database tables:

- `wcl_option_sets` - Stores option set definitions
- `wcl_options` - Stores individual options within sets
- `wcl_option_values` - Stores values for select-type options
- `wcl_rules` - Stores conditional logic rules
- `wcl_product_option_sets` - Links option sets to products

## File Structure

```
woo-conditional-logic/
├── woo-conditional-logic.php           # Main plugin file
├── README.md                           # Documentation
├── includes/                           # Core PHP classes
│   ├── class-wcl-install.php          # Installation & database setup
│   ├── class-wcl-option-sets.php      # Option sets management
│   ├── class-wcl-options.php          # Options & values management
│   ├── class-wcl-rules.php            # Conditional rules engine
│   ├── class-wcl-frontend.php         # Frontend display & logic
│   └── admin/                          # Admin functionality
│       ├── class-wcl-admin.php        # Main admin class
│       ├── class-wcl-admin-option-sets.php
│       ├── class-wcl-admin-products.php
│       └── views/                      # Admin template files
│           ├── option-sets-list.php
│           ├── option-set-edit.php
│           └── product-options-tab.php
├── assets/                             # Frontend assets
│   ├── css/
│   │   ├── frontend.css                # Frontend styles
│   │   └── admin.css                   # Admin styles
│   └── js/
│       ├── frontend.js                 # Frontend JavaScript
│       └── admin.js                    # Admin JavaScript
└── languages/                          # Translation files
    └── woo-conditional-logic.pot
```

## Hooks & Filters

### Actions
- `wcl_before_option_display` - Before option is displayed
- `wcl_after_option_display` - After option is displayed
- `wcl_rule_applied` - When a rule is applied

### Filters
- `wcl_option_types` - Modify available option types
- `wcl_price_modifier` - Modify price calculation
- `wcl_rule_conditions` - Modify rule conditions

## Customization

### Custom Option Types
You can add custom option types by filtering the option types array:

```php
add_filter('wcl_option_types', function($types) {
    $types['custom_type'] = array(
        'label' => 'Custom Type',
        'description' => 'A custom option type',
        'multiple' => false,
        'has_price' => true,
        'has_image' => false,
        'has_color' => false
    );
    return $types;
});
```

### Custom Styling
Override the default styles by adding CSS to your theme:

```css
.wcl-product-options {
    /* Your custom styles */
}
```

## Performance

- Efficient database queries with proper indexing
- AJAX-powered conditional logic for smooth user experience
- Caching of option sets and rules
- Optimized JavaScript with event delegation

## Troubleshooting

### Common Issues

1. **Options not showing** - Check that the option set is assigned to the product
2. **Rules not working** - Verify rule conditions and ensure JavaScript is enabled
3. **Price not updating** - Check that price modifiers are set correctly
4. **Styling issues** - Check for theme CSS conflicts

### Debug Mode
Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please create an issue on the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Inspired by the Advanced Product Options functionality described in the [Infinite Apps blog post](https://infiniteapps.net/blog/how-to-create-conditional-product-options?wl=true).

## Changelog

### 1.0.0
- Initial release
- Option sets management
- Conditional logic rules
- Product integration
- Frontend display and validation
- Admin interface
