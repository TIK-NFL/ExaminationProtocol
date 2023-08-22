# Examination Protocol

[![License](https://img.shields.io/badge/license-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.en.html)
![Version](https://img.shields.io/badge/version-v0.5.0-green.svg)

Examination Protocol is a plugin that streamlines the process of managing and conducting examinations. This README provides an overview of the plugin's features, installation instructions, configuration options, dependencies, and how to contribute.

## Table of Contents

- [Installation](#installation)
- [Dependencies](#dependencies)
- [Configuration](#configuration)
- [Suggestions](#suggestions)
- [License](#license)

## BETA Disclaimer
The plugin is currently in a *BETA* state. Please do not use it in production.
If you find any bugs or side effects, please let me know, either by posting an issue in this git repository or later by submitting a Mantis ticket.

## Installation

Follow these steps to install the Examination Protocol plugin:

1. Copy the plugin files from this repository to the following directory in your ILIAS installation:
   ```bash
    ILIAS/Customizing/global/plugins/UIComponent/UserInterfaceHook/ExaminationProtocol/
   ```

2. In the terminal, navigate to the `ExaminationProtocol` folder:
   ```bash
   cd ILIAS/Customizing/global/plugins/UIComponent/UserInterfaceHook/ExaminationProtocol/
   ```

3. Run the following command to install required dependencies:
   ```bash
   composer install --no-dev
   ```

4. Since this is currently an unofficial plugin, you need to modify the `module.xml` file located at `ILIAS/Modules/Test/module.xml`. Add the following XML snippet within the `<pluginslots>` section:
   ```xml
   <pluginslots>
       <!-- ... Other slots ... -->
       <pluginslot id="texa" name="ExaminationProtocol" />
   </pluginslots>
   ```

## Dependencies

To use the Examination Protocol plugin, ensure you have the following dependencies:

- **ILIAS**: Version 7 current release
- **PHP**: Version 7.4
- **Composer**: To manage PHP dependencies

## Configuration

In the plugin configuration menu, you can configure the plugin's behavior:

- Show Examination Protocol: You can choose to display the examination protocol on all ILIAS Test Objects.
- Hide Examination Protocol: You can choose to hide the examination protocol on all ILIAS Test Objects.

A manual configuration option is also in development.

## Suggestions

I'm still in the learning how the gears in ILIAS turn and connect to each other.
Therefore, constructive suggestions for improvement are always welcome.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

For more information about the GPLv3 license, you can visit the [GNU website](https://www.gnu.org/licenses/gpl-3.0.en.html).