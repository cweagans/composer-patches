<?php

namespace cweagans\Composer\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

class PackageInstallationTest extends TestCase
{

  private $filesystem;

  private $tempDir;

  private $packeDir;

  private $vendorDir;

  private $composerLock;

  protected function setUp(): void
  {
    parent::setUp();
    $this->filesystem = new Filesystem();
    $this->tempDir = sys_get_temp_dir() . '/composer-test-' . uniqid();
    $this->packeDir = $this->tempDir . '/tests/_data/dep-test-package';
    $this->vendorDir = $this->packeDir . '/vendor';
    $this->composerLock = $this->packeDir . '/composer.lock';

    // Create temp directory.
    $this->filesystem->mkdir($this->tempDir);

    // Copy this packages full code into the temp directory.
    $this->filesystem->mirror(
      __DIR__ . '/../',
      $this->tempDir
    );
  }

  protected function tearDown(): void
  {
    // Clean up the temporary directory
    if ($this->filesystem->exists($this->tempDir) && !getenv(
        'COMPOSER_TESTS_KEEP_TEMP_DIR'
      )) {
      $this->filesystem->remove($this->tempDir);
    }
    parent::tearDown();
  }

  protected function isVerbose(): bool
  {
    return in_array('--verbose', $_SERVER['argv']) ||
      in_array('-v', $_SERVER['argv']) ||
      in_array('-vv', $_SERVER['argv']) ||
      in_array('-vvv', $_SERVER['argv']);
  }

  public function testPackageInstallation()
  {
    // Create new Composer application
    $application = new Application();
    $application->setAutoExit(false);

    // Run composer install
    $input = new ArrayInput([
      'command' => 'install',
      '--working-dir' => $this->packeDir,
      '--no-interaction' => true,
      '--no-progress' => true,
    ]);

    $bufferedOutput = new BufferedOutput();
    $exitCode = $application->run($input, $bufferedOutput);
    // Get the output content
    $outputContent = $bufferedOutput->fetch();


    // Assert composer install was successful.
    // This doesn't fail if patching fails!
    $this->assertEquals(
      0,
      $exitCode,
      'Composer install failed: ' . $outputContent
    );

    // If the output contains patching related error, display it to console
    if (strpos($outputContent, 'Could not apply patch!') !== false && $this->isVerbose()) {
      $consoleOutput = new ConsoleOutput();
      $consoleOutput->write($outputContent);
    }

    // Verify that vendor directory exists.
    $this->assertTrue(
      $this->filesystem->exists($this->vendorDir),
      'Vendor directory was not created - ' . $outputContent
    );

    // Verify composer.lock was created
    $this->assertTrue(
      $this->filesystem->exists($this->composerLock),
      'composer.lock file was not created - ' . $outputContent
    );

    // Check that the local patch from the dependency has been applied.
    $this->assertTrue(
      $this->filesystem->exists(
        $this->vendorDir . '/cweagans/composer-patches-testrepo/src/LocalPatchFromDependency.php'
      ),
      'Local patch from dependency was not applied - ' . $outputContent
    );

    // Check that the local patch from the project has been applied.
    $this->assertTrue(
      $this->filesystem->exists(
        $this->vendorDir . '/cweagans/composer-patches-testrepo/src/LocalPatchFromProject.php'
      ),
      'Local patch from project was not applied - ' . $outputContent
    );

    // Check that the remote patch from a dependency has been applied.
    $this->assertTrue(
      $this->filesystem->exists(
        $this->vendorDir . '/cweagans/composer-patches-testrepo/src/OneMoreTest.php'
      ),
      'Remote patch from dependency was not applied - ' . $outputContent
    );

    // Check that the remote patch from the project has been applied.
    // Would like to use a second remote patch but can't because these tests are
    // built to validate the workaround for the github rate limiting issue.
    // Which we would hit when downloading 2 patches within a minute.
    // Add the following patch once the rate limiting issue on github is fixed.
    /*$this->assertTrue(
      $this->filesystem->exists(
        $this->vendorDir . '/cweagans/composer-patches-testrepo/src/YetAnotherTest.php'
      ),
      'Remote patch from project was not applied'
    );*/
  }
}
