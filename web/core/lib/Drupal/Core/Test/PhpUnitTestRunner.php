<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Run PHPUnit-based tests.
 *
 * This class runs PHPUnit-based tests and converts their JUnit results to a
 * format that can be stored in the {simpletest} database schema.
 *
 * This class is internal and not considered to be API.
 *
 * @code
 * $runner = PhpUnitTestRunner::create(\Drupal::getContainer());
 * $results = $runner->execute($test_run, $test_list['phpunit']);
 * @endcode
 *
 * @internal
 */
class PhpUnitTestRunner implements ContainerInjectionInterface {

  /**
   * Constructs a test runner.
   *
   * @param string $appRoot
   *   Path to the application root.
   * @param string $workingDirectory
   *   Path to the working directory. JUnit log files will be stored in this
   *   directory.
   */
  public function __construct(
    protected string $appRoot,
    protected string $workingDirectory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      (string) $container->getParameter('app.root'),
      (string) $container->get('file_system')->realpath('public://simpletest')
    );
  }

  /**
   * Returns the path to use for PHPUnit's --log-junit option.
   *
   * @param int $test_id
   *   The current test ID.
   *
   * @return string
   *   Path to the PHPUnit XML file to use for the current $test_id.
   *
   * @internal
   */
  public function xmlLogFilePath(int $test_id): string {
    return $this->workingDirectory . '/phpunit-' . $test_id . '.xml';
  }

  /**
   * Returns the command to run PHPUnit.
   *
   * @return string
   *   The command that can be run through exec().
   *
   * @internal
   */
  public function phpUnitCommand(): string {
    // Load the actual autoloader being used and determine its filename using
    // reflection. We can determine the vendor directory based on that filename.
    $autoloader = require $this->appRoot . '/autoload.php';
    $reflector = new \ReflectionClass($autoloader);
    $vendor_dir = dirname($reflector->getFileName(), 2);

    // The file in Composer's bin dir is a *nix link, which does not work when
    // extracted from a tarball and generally not on Windows.
    $command = $vendor_dir . '/phpunit/phpunit/phpunit';
    if (str_starts_with(PHP_OS, 'WIN')) {
      // On Windows it is necessary to run the script using the PHP executable.
      $php_executable_finder = new PhpExecutableFinder();
      $php = $php_executable_finder->find();
      $command = $php . ' -f ' . escapeshellarg($command) . ' --';
    }
    return $command;
  }

  /**
   * Executes the PHPUnit command.
   *
   * @param string[] $unescaped_test_classnames
   *   An array of test class names, including full namespaces, to be passed as
   *   a regular expression to PHPUnit's --filter option.
   * @param string $phpunit_file
   *   A filepath to use for PHPUnit's --log-junit option.
   * @param int $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   * @param string[] $output
   *   (optional) The output by running the phpunit command. If provided, this
   *   array will contain the lines output by the command.
   *
   * @internal
   */
  public function runCommand(array $unescaped_test_classnames, string $phpunit_file, ?int &$status = NULL, ?array &$output = NULL): void {
    global $base_url;
    // Setup an environment variable containing the database connection so that
    // functional tests can connect to the database.
    $process_environment_variables = [
      'SIMPLETEST_DB' => Database::getConnectionInfoAsUrl(),
    ];

    // Setup an environment variable containing the base URL, if it is available.
    // This allows functional tests to browse the site under test. When running
    // tests via CLI, core/phpunit.xml.dist or core/scripts/run-tests.sh can set
    // this variable.
    if ($base_url) {
      $process_environment_variables['SIMPLETEST_BASE_URL'] = $base_url;
      $process_environment_variables['BROWSERTEST_OUTPUT_DIRECTORY'] = $this->workingDirectory;
    }
    $phpunit_bin = $this->phpUnitCommand();

    $command = [
      $phpunit_bin,
      '--log-junit',
      $phpunit_file,
    ];

    // Optimized for running a single test.
    if (count($unescaped_test_classnames) == 1) {
      $class = new \ReflectionClass($unescaped_test_classnames[0]);
      $command[] = $class->getFileName();
    }
    else {
      // Double escape namespaces so they'll work in a regexp.
      $escaped_test_classnames = array_map(function ($class) {
        return addslashes($class);
      }, $unescaped_test_classnames);

      $filter_string = implode("|", $escaped_test_classnames);
      $command = array_merge($command, [
        '--filter',
        $filter_string,
      ]);
    }

    $process = new Process($command, \Drupal::root() . "/core", $process_environment_variables);
    $process->setTimeout(NULL);
    $process->run();
    $output = explode("\n", $process->getOutput());
    $status = $process->getExitCode();
  }

  /**
   * Executes PHPUnit tests and returns the results of the run.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param string[] $unescaped_test_classnames
   *   An array of test class names, including full namespaces, to be passed as
   *   a regular expression to PHPUnit's --filter option.
   * @param int $status
   *   (optional) The exit status code of the PHPUnit process will be assigned
   *   to this variable.
   *
   * @return array
   *   The parsed results of PHPUnit's JUnit XML output, in the format of
   *   {simpletest}'s schema.
   *
   * @internal
   */
  public function execute(TestRun $test_run, array $unescaped_test_classnames, ?int &$status = NULL): array {
    $phpunit_file = $this->xmlLogFilePath($test_run->id());
    // Store output from our test run.
    $output = [];
    $this->runCommand($unescaped_test_classnames, $phpunit_file, $status, $output);

    if ($status == TestStatus::PASS) {
      return JUnitConverter::xmlToRows($test_run->id(), $phpunit_file);
    }
    return [
      [
        'test_id' => $test_run->id(),
        'test_class' => implode(",", $unescaped_test_classnames),
        'status' => TestStatus::label($status),
        'message' => 'PHPUnit Test failed to complete; Error: ' . implode("\n", $output),
        'message_group' => 'Other',
        'function' => implode(",", $unescaped_test_classnames),
        'line' => '0',
        'file' => $phpunit_file,
      ],
    ];
  }

  /**
   * Logs the parsed PHPUnit results into the test run.
   *
   * @param \Drupal\Core\Test\TestRun $test_run
   *   The test run object.
   * @param array[] $phpunit_results
   *   An array of test results, as returned from
   *   \Drupal\Core\Test\JUnitConverter::xmlToRows(). Can be the return value of
   *   PhpUnitTestRunner::execute().
   */
  public function processPhpUnitResults(TestRun $test_run, array $phpunit_results): void {
    foreach ($phpunit_results as $result) {
      $test_run->insertLogEntry($result);
    }
  }

  /**
   * Tallies test results per test class.
   *
   * @param string[][] $results
   *   Array of results in the {simpletest} schema. Can be the return value of
   *   PhpUnitTestRunner::execute().
   *
   * @return int[][]
   *   Array of status tallies, keyed by test class name and status type.
   *
   * @internal
   */
  public function summarizeResults(array $results): array {
    $summaries = [];
    foreach ($results as $result) {
      if (!isset($summaries[$result['test_class']])) {
        $summaries[$result['test_class']] = [
          '#pass' => 0,
          '#fail' => 0,
          '#exception' => 0,
          '#debug' => 0,
        ];
      }

      switch ($result['status']) {
        case 'pass':
          $summaries[$result['test_class']]['#pass']++;
          break;

        case 'fail':
          $summaries[$result['test_class']]['#fail']++;
          break;

        case 'exception':
          $summaries[$result['test_class']]['#exception']++;
          break;

        case 'debug':
          $summaries[$result['test_class']]['#debug']++;
          break;
      }
    }
    return $summaries;
  }

}
