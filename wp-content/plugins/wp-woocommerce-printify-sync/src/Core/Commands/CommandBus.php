<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Commands;

class CommandBus {
    private array $handlers = [];
    private EventManagerInterface $eventManager;
    
    public function __construct(EventManagerInterface $eventManager) {
        $this->eventManager = $eventManager;
    }

    public function register(string $command, CommandHandlerInterface $handler): void {
        $this->handlers[$command] = $handler;
    }
    
    public function dispatch(CommandInterface $command): void {
        $class = get_class($command);
        
        if (!isset($this->handlers[$class])) {
            throw new \RuntimeException("No handler for command {$class}");
        }
        
        $this->handlers[$class]->handle($command);
        $this->eventManager->dispatch('command.executed', ['command' => $class]);
    }
}
