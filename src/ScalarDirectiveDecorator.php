<?php

declare(strict_types=1);

namespace Compwright\GraphqlScalars;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ScalarDirectiveDecorator
{
    private ?ContainerInterface $container;

    private LoggerInterface $logger;

    public function __construct(?ContainerInterface $container = null, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $typeDefinitionMap
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $config, TypeDefinitionNode $typeDefinition, array $typeDefinitionMap): array
    {
        // Attach the custom scalar handler methods defined in the @scalar(class) directive
        if ($typeDefinition instanceof ScalarTypeDefinitionNode) {
            $this->logger->debug('Inspecting scalar ' . $config['name']);

            foreach ($this->readDirectives($typeDefinition) as $directive => $args) {
                if ($directive === 'scalar' && isset($args['class'])) {
                    $class = strval($args['class']);

                    $this->logger->debug('Attaching custom scalar class ' . $class);

                    $scalarInstance = $this->container
                        ? $this->container->get($class)
                        : new $class();

                    $config['serialize'] = [$scalarInstance, 'serialize'];
                    $config['parseValue'] = [$scalarInstance, 'parseValue'];
                    $config['parseLiteral'] = [$scalarInstance, 'parseLiteral'];
                }
            }
        }

        return $config;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readDirectives(ScalarTypeDefinitionNode $node): array
    {
        $directives = [];

        /** @var DirectiveNode $directive */
        foreach ($node->directives as $directive) {
            $args = [];

            /** @var ArgumentNode $arg */
            foreach ($directive->arguments as $arg) {
                $args[$arg->name->value] = $arg->value->value ?? null;
            }

            $directives[$directive->name->value] = $args;
        }

        return $directives;
    }
}
