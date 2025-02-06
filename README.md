# PED-EU-NET WordPress Plugin

*Custom WP plugin for PED-EU-NET website*

## Features

- Rendering PED DB case studies on an interactive map
- Rendering PED DB case studies as tables (overview + detail)
- Rendering PED DB projects as tables (overview + detail)
- Exporting PED DB case studies to CSV and PDF
- Managing and securing forms for new PED DB entries (via Gravity Forms)
- Managing PED DB case studies and projects via quick actions
- Linking PED DB case studies and projects with corresponding form entries (of Gravity Forms)

### Shortcodes

- `pedeu_case_studies_map` - rendering an interactive map with all case studies (and filters), where users can open a specific case study (its detail as a table view); it is also possible to compare multiple case studies side-by-side
- `pedeu_case_studies_table` - rendering a table of all case studies (with filters and pagination), where users can open a specific case study (its detail as a table view); it is also possible to compare multiple case studies side-by-side
- `pedeu_projects_table` - rendering a table of all projects (with filters and pagination), where users can open a specific project (its simple detail as a table view)

### PED DB Admin

There is page "DB Editor" added to WP Admin interface which allows to send invitations to a project / case study form and then manage these invitations. Non-invited users (without the link containing unique secret code) will not be able to access the form. The entry can be deactivated, (re)activated, or deleted permanently.

## Contributing

### Structure

* `includes/`
  * `admin.php` = file containing all functionality (or including it) related to WP Admin
  * `bootstrap.php` = file for bootstraping the plugin (always loaded as the second one)
  * `utils.php` = file for generic and shared functions (always loaded as the first one)
  * (other = other PHP files are named according to their concern and are included in one of the other files or from `ped-eu-net.php` file
* `static/` = static files containing resources directly loaded by clients (further separated to `css`, `images`, and `js`)
* `CHANGELOG.md` = history of changes
* `ped-eu-net.php` = entry file with WP plugin metadata, constants, and includes of other files in a specific order
* `README.md` = this file describing the WP plugin, its features and contributing information

### Conventions

- All files should be named using kebab-case.
- All source code files should be formatted using PhpStorm's default settings.
- Every function, constant, and class must be documented using comments in the source code.
- There must be no "dead" code or other code smells detected.
- All functions and procedures must be prefixed by `pedeu_` for consistency and uniqueness.
- Always keep CHANGELOG and README up to date.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for more details.
