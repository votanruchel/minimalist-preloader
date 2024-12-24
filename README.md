# Minimalist Loader

A lightweight and optimized WordPress plugin designed specifically for publishers using Google Ad Manager. This plugin provides a minimalist preloader that elegantly handles the page loading state while waiting for Google Ad Manager to initialize.

## Features

- 🚀 Lightweight and performance-optimized
- ⚙️ Fully customizable appearance
- 🎯 Specifically designed for Google Ad Manager integration
- 🔄 Smart loading management with minimum and maximum time controls
- 🌫️ Optional background blur effect
- 📱 Responsive and cross-browser compatible
- ⏱️ Configurable GPT event listening

## Benefits for Publishers

- **Improved User Experience**: Prevents content shifting and flickering during ad loading
- **Better Ad Performance**: Ensures ads are properly loaded before displaying content
- **Customizable Loading Times**: Set minimum and maximum loading durations to balance user experience and ad delivery
- **Flexible Integration**: Works with various Google Publisher Tag events
- **Minimal Performance Impact**: Optimized code with minimal overhead

## Installation

### Manual Installation

1. Download the latest release from the [GitHub repository](https://github.com/votanruchel/minimalist-preloader/)
2. Upload the plugin folder to the `/wp-content/plugins/` directory of your WordPress installation
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings under 'Settings > Minimalist Loader'

### Via WordPress Admin Panel

1. Go to 'Plugins > Add New' in your WordPress admin panel
2. Search for 'Minimalist Loader'
3. Click 'Install Now' and then 'Activate'
4. Configure the plugin settings under 'Settings > Minimalist Loader'

## Configuration

1. Navigate to 'Settings > Minimalist Loader' in your WordPress admin panel
2. Customize the following options:
   - Spinner Color
   - Spinner Background Color
   - Screen Background Color
   - Background Blur Effect
   - Minimum Display Time
   - Maximum Display Time
   - Google Ad Manager Event Trigger

## Usage

The plugin works automatically after activation and configuration. It will:

1. Show a loading spinner when the page loads
2. Wait for Google Ad Manager to initialize
3. Respect minimum and maximum loading times
4. Optionally wait for specific GPT events
5. Smoothly fade out when loading is complete

## Advanced Configuration

### GPT Event Listening

You can configure the plugin to wait for specific Google Publisher Tag events:

- `slotRenderEnded`: Wait for specific ad slots to render
- `impressionViewable`: Wait for ads to become viewable
- Leave empty to only wait for initial GPT loading

## Contributing

We welcome contributions to improve Minimalist Loader! Here's how you can help:

1. Fork the [repository](https://github.com/votanruchel/minimalist-preloader/)
2. Create a new branch (`git checkout -b feature/improvement`)
3. Make your changes
4. Run tests if available
5. Commit your changes (`git commit -am 'Add new feature'`)
6. Push to the branch (`git push origin feature/improvement`)
7. Create a Pull Request

### Development Setup

1. Clone the repository: `git clone https://github.com/votanruchel/minimalist-preloader/`
2. Set up a local WordPress development environment
3. Symlink or copy the plugin to your WordPress plugins directory
4. Activate the plugin and start developing

### Coding Standards

- Follow WordPress PHP Coding Standards
- Maintain compatibility with PHP 7.0+
- Ensure backward compatibility with WordPress 5.0+
- Document any new functions or features

## Support

- Create an issue in the [GitHub repository](https://github.com/votanruchel/minimalist-preloader/issues)
- Visit our [support forum](https://wordpress.org/support/plugin/minimalist-loader)
- Contact us through our [website](https://votan.dev)

## License

This project is licensed under the GPL2 License - see the [LICENSE](LICENSE) file for details.

## Credits

Created and maintained by [Votan Ruchel](https://votan.dev)

## Changelog

### 1.0.0
- Initial release
- Basic loading functionality
- Customizable appearance
- GPT event integration
- Admin configuration panel
