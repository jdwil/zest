<?php
declare(strict_types=1);

namespace JDWil\Zest\Command;

use JDWil\PhpGenny\Builder\Builder;
use JDWil\PhpGenny\Builder\BuilderFactory;
use JDWil\PhpGenny\Writer\TypeWriter;
use JDWil\Zest\Builder\ClassGenerator;
use JDWil\Zest\Builder\Config;
use JDWil\Zest\Exception\InvalidSchemaException;
use JDWil\Zest\Exception\ValidationException;
use JDWil\Zest\Model\SchemaCollection;
use JDWil\Zest\Parser\XsdParser;
use JDWil\Zest\Util\NamespaceUtil;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GenerateCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected function configure()
    {
        $this
            ->setName('generate')

            // php-genny options
            ->addOption('no-docblocks', 'nd', InputOption::VALUE_NONE, 'Suppress generation of docblocks in classes')
            ->addOption('no-strict-types', 'nst', InputOption::VALUE_NONE, 'Do not declare strict types in files')
            ->addOption('php-version', 'pv', InputOption::VALUE_REQUIRED, 'Target PHP version for generated code. Available versions: ' . implode(', ', Builder::$PHP_VERSIONS), 70)

            // code style options
            ->addOption('fluid-setters', 'fs', InputOption::VALUE_NONE, 'Generate fluid setters and adders')
            ->addOption('fluid-builder', 'fb', InputOption::VALUE_NONE, 'Generate a fluid builder')

            // generator options
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Directory to store output files')
            ->addOption('xsd-file', 'xf', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'An xsd file to process or a directory containing your xsd files')
            ->addOption('namespace-prefix', 'ns', InputOption::VALUE_REQUIRED, 'PSR-4 namespace prefix', '')
            ->addOption('no-output', 'no', InputOption::VALUE_NONE, 'Do not generate any output files (useful for debugging)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->filesystem = new Filesystem();

        $files = $this->getInputFiles();
        $schemas = $this->runParser($files);

        $classes = $this->generateClasses($schemas);
        if (!$input->getOption('no-output')) {
            $this->writeClasses($classes);
        }
    }

    /**
     * @param array $classes
     */
    private function writeClasses(array $classes)
    {
        $writer = new TypeWriter($this->buildFactory(), new Standard());
        $writer->setNamespacePrefix($this->input->getOption('namespace-prefix'));
        $writer->setBaseDirectory($this->input->getOption('output'));

        try {
            $writer->writeAll($classes);
        } catch (\Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            exit(1);
        }
    }

    /**
     * @return BuilderFactory
     */
    private function buildFactory(): BuilderFactory
    {
        $factory = new BuilderFactory();
        if (!$this->input->getOption('no-docblocks')) {
            $factory->autoGenerateDocBlocks();
        }

        if (!$this->input->getOption('no-strict-types')) {
            $factory->useStrictTypes();
        }

        $factory->setPhpTargetVersion((int) $this->input->getOption('php-version'));

        return $factory;
    }

    /**
     * @param SchemaCollection $schemas
     * @return array
     */
    private function generateClasses(SchemaCollection $schemas): array
    {
        $generator = new ClassGenerator($this->buildConfig(), $this->output);
        return $generator->buildClasses($schemas);
    }

    /**
     * @param array $inputFiles
     * @return SchemaCollection
     */
    private function runParser(array $inputFiles): SchemaCollection
    {
        $parser = new XsdParser();
        foreach ($inputFiles as $inputFile) {
            try {
                $parser->parseXsdFile($inputFile);
            } catch (ValidationException $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                exit(1);
            } catch (InvalidSchemaException $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                exit(1);
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                exit(1);
            }
        }

        return $parser->getSchemas();
    }

    /**
     * @return Config
     */
    private function buildConfig(): Config
    {
        $config = new Config($this->input->getOption('namespace-prefix'));

        if ($this->input->getOption('fluid-setters')) {
            $config->generateFluidSetters = true;
        }

        if ($this->input->getOption('fluid-builder')) {
            $config->generateFluidBuilder = true;
        }

        return $config;
    }

    /**
     * @return array
     */
    private function getInputFiles(): array
    {
        $ret = [];

        /** @var string[] $filePaths */
        $filePaths = $this->input->getOption('xsd-file');

        foreach ($filePaths as $filePath) {
            if (is_dir($filePath)) {
                $files = new Finder();
                $files->in($filePath)->name('*.xsd');
                foreach ($files as $file) {
                    $ret[] = $file->getPathname();
                }
            } else {
                $ret[] = $filePath;
            }
        }

        return $ret;
    }
}
