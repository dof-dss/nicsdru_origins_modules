
## NICSDru Layouts

A collection of Drupal Layout Build layouts for use across multiple sites.
Although this module provides custom layouts it does not provide the CSS for these and you will have to create this for each layout within your theme. 

### Installing ###
To install, enable the core _Layout Builder_ and _Layout Discovery_ modules before installing this module.

### Configuration ###
Each layout configuration form provides options for extra classes and a 'reverse layout' option.
The 'reverse layout' checkbox will simply add an additional 'reverse' class to your layout wrapper and can be used to flip the display order of sections via CSS. 

#### Adding additional layouts ####
1. Create a new layout plugin that extends NicsDruLayout within /src/Plugin/Layout.
2. Annotate the class with the required Layout annotations. 
   The current naming policy is to use the surnames of famous architects.
3. Create a template for your layout under /templates.
4. Create CSS for the markup in your template within your theme.
