<?php

namespace NmDigitalHub\LaravelSumitPayment\Traits;

use Illuminate\Support\Facades\Event;

trait HasCustomHooks
{
    /**
     * Apply filter hook similar to WordPress apply_filters.
     * 
     * This allows developers to modify values using Laravel events.
     * 
     * @param string $hookName The name of the hook
     * @param mixed $value The value to be filtered
     * @param mixed ...$args Additional arguments
     * @return mixed The filtered value
     */
    protected function applyFilters(string $hookName, $value, ...$args)
    {
        $eventClass = $this->getEventClass($hookName);
        
        if (class_exists($eventClass)) {
            $event = new $eventClass($value, ...$args);
            Event::dispatch($event);
            
            if (method_exists($event, 'getValue')) {
                return $event->getValue();
            }
        }
        
        return $value;
    }

    /**
     * Execute action hook similar to WordPress do_action.
     * 
     * @param string $hookName The name of the hook
     * @param mixed ...$args Arguments to pass to the action
     */
    protected function doAction(string $hookName, ...$args)
    {
        $eventClass = $this->getEventClass($hookName);
        
        if (class_exists($eventClass)) {
            Event::dispatch(new $eventClass(...$args));
        }
    }

    /**
     * Get event class for hook name.
     * 
     * @param string $hookName
     * @return string
     */
    protected function getEventClass(string $hookName): string
    {
        $className = str_replace('_', '', ucwords($hookName, '_'));
        return "App\\Events\\Sumit\\{$className}";
    }
}
