<?php
declare(strict_types=1);

namespace JDWil\Zest\Builder;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractTypeFactory
 * @package JDWil\Zest\Builder
 */
abstract class AbstractTypeFactory
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * AbstractTypeFactory constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $message
     */
    protected function debug(string $message, string $className = null)
    {
        if (null !== $className) {
            $message = '<info>[' . $className . ']: </info>' . $message;
        }

        if ($this->output->isDebug()) {
            $this->output->writeln($message);
        }
    }
}
