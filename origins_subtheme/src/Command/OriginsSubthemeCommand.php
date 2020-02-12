<?php
namespace Drupal\origins_subtheme\Command;
use Drupal\ckeditor_find\Plugin\CKEditorPlugin\Find;
use Drupal\Console\Command\ShellCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class OriginsSubthemeCommand.
 *
 * @DrupalCommand (
 *     extension="origins_subtheme",
 *     extensionType="module"
 * )
 */
class OriginsSubthemeCommand extends ContainerAwareCommand {

  /**
   * @var Io.
   */
  private $Io;

  /**
   * @var Filesystem Object.
   */
  private $filesystem;

  /**
   * @var Finder component from Symfony.
   */
  private $finder;

  /**
   * @var Parent Themes.
   */
  private $parent_themes;

  /**
   * @var Friendly name for subtheme.
   */
  private $friendly_name;

  /**
   * @var Descriptionm for Subtheme name for subtheme.
   */
  private $description;

  /**
   * const for targeted placeholder.
   */
  const PLACEHOLDER = 'STARTERKIT';

  /**
   * const for base theme directory string.
   */
  const BASE_THEME_DIRECTORY = 'themes/custom/';

  /**
   * Initializes all class vars before execute and interact.
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function initialize(InputInterface $input, OutputInterface $output)
  {
    // Setting up Io
    $this->Io = new DrupalStyle($input, $output);

    // Instantiate Symfony file system object.
    $this->filesystem = new Filesystem();

    // Instantiates Symfony Finder object.
    $this->finder = new Finder();

    // Load Viable Parent themes that have Starterkit directory present.
    $this->parent_themes = $this->get_startkit_themes();
  }

  /**
   * Interact functionality to prompt for arguments/options.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    // Init Variables, check for existing values.
    $name = $input->getArgument('name');
    $parent_theme = $input->getOption('parent_theme');
    $description = $input->getOption('description');

    // Subtheme Name.
    while(!$this->machine_name_validation($name)) {
      $name = $this->Io->askEmpty(
        'Please provide a name for the subtheme',
        null
      );
    }

    // Saves Friendly Input name.
    $this->friendly_name = $name;

    // Stores argument as machine friendly name.
    $input->setArgument('name', $this->machine_name_validation($name)); // Validation function to sanitize input.

    // Parent Theme.
    while(is_null($parent_theme)) {
      $parent_theme = $this->Io->choice(
        'Select which Theme to Subtheme off.',
        array_keys($this->parent_themes)
      );
    }
    $input->setOption('parent_theme', $parent_theme); // Machine name set as value.

    // Set Description (OPTIONAL)
    $description = $this->Io->askEmpty(
      'Please provide a description for the subtheme',
      'Subtheme of ' . $parent_theme
    );
    $input->setOption('description', $description);
    $this->description = $description;
  }

  /**
   * Configure functionality for command.
   */
  protected function configure() {
    $this->setName('origins:subtheme')
      ->setDescription('Automated Subtheme command')
      ->addArgument(
      'name',
      InputArgument::REQUIRED,
      'Name for subtheme',
      null)
      ->addOption('parent_theme', '', InputOption::VALUE_REQUIRED,
        'Parent Theme to subtheme off.')
      ->addOption('description', '', InputOption::VALUE_OPTIONAL,
        'Description for new theme.');
  }

  /**
   * Main body of code to execute for command.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|void|null
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    //copy directory contents.
    try {
      $this->filesystem->mirror(
        self::BASE_THEME_DIRECTORY . $input->getOption('parent_theme').'/' . self::PLACEHOLDER,
        self::BASE_THEME_DIRECTORY . $input->getArgument('name')
      );

      $this->iterate_directory($input->getArgument('name'));

    } catch (IOExceptionInterface $exception) {
      $this->Io->info('Error encountered when making directory at: ' . $exception->getPath());
    }
  }

  /**
   * Cleans up name to be machine friendly.
   *
   * @param $name
   * @return mixed
   */
  public function machine_name_validation($name)
  {
    // Clean up the machine name.
    $machine_name = str_replace(' ', '_', strtolower($name));

    // Blacklisted characters.
    $search = array(
      '/[^a-z0-9_]/',
      '/^[^a-z]+/',
    );

    // Replaces restricted characters.
    $machine_name = preg_replace($search, '', $machine_name);

    // Returns validated machine name for subtheme.
    return $machine_name;
  }

  /**
   * Returns all Themes with Startkit Directory placeholder
   *
   * @return array
   */
  public function get_startkit_themes()
  {
    // Init Var.
    $parentThemeOptions = [];

    // Loads all Installed Themes.
    $themes = \Drupal::service('theme_handler')->listInfo();

    // Check each theme for Starterkit directory, if exists add to options list.
    foreach($themes as $theme) {
      if ($this->filesystem->exists($theme->getPath().'/'.self::PLACEHOLDER.'/')) {
        $parentThemeOptions[$theme->getName()] = $theme;
      }
    }

    // Validation to check if array is empty - if so exit command as no subtheme can be generated.
    if (empty($parentThemeOptions)) {
      $this->Io->info('No valid Parent theme was found to subtheme.');
      exit();
    }

    // Will only hit this after check, but ensures a return statement.
    return $parentThemeOptions;
  }

  /**
   * Iterates Directories and renames files as well as file contents.
   *
   * @param $parent_theme
   * @param $subtheme_name
   */
  public function iterate_directory($subtheme_name)
  {
    $file_name_search = new Finder();
    $file_content_search = new Finder();

    // Finds all files with Placeholder in file name.
    $file_name_search->name('*'.self::PLACEHOLDER.'*')->in(self::BASE_THEME_DIRECTORY . $subtheme_name);

    foreach ($file_name_search as $result) {
      //foreach file rename using PHP rename function.
      rename(
        self::BASE_THEME_DIRECTORY . $subtheme_name . '/' . $result->getFilename(),
        self::BASE_THEME_DIRECTORY . $subtheme_name .'/'. str_replace(self::PLACEHOLDER, $subtheme_name, $result->getFilename())
      );
    }

    // Finds all file with placeholder in file contents.
    $file_content_search->contains('/STARTERKIT/')->in(self::BASE_THEME_DIRECTORY . $subtheme_name);

    foreach ($file_content_search as $result) {
      // info yml special case
      if(strpos($result->getFilename(), 'info.yml')) {
        try {
          // Parse YAML file into array
          $info_yml_array = Yaml::parseFile(self::BASE_THEME_DIRECTORY . $subtheme_name . '/' . $result->getFilename());
          // Set Name attribute to User Input name
          $info_yml_array['name'] = $this->friendly_name;
          // Set Description
          $info_yml_array['description'] = $this->description;
          // Dump array into YAML format
          $yaml = Yaml::dump($info_yml_array);
          // Set new contents in YAML file
          file_put_contents(self::BASE_THEME_DIRECTORY . $subtheme_name . '/' . $result->getFilename(), $yaml);
        } catch (ParseException $exception) {
          $this->Io->info('Unable to parse the info YAML file: ' . $exception->getMessage());
        }
      }

      // Load each files contents
      $contents = file_get_contents(self::BASE_THEME_DIRECTORY . $subtheme_name .'/' . $result->getFilename());
      file_get_contents(self::BASE_THEME_DIRECTORY . $subtheme_name . '/' . $result->getFilename());
      $contents = str_replace(self::PLACEHOLDER, $subtheme_name, $contents);
      file_put_contents(self::BASE_THEME_DIRECTORY . $subtheme_name . '/' . $result->getFilename(), $contents);
    }
  }

}
