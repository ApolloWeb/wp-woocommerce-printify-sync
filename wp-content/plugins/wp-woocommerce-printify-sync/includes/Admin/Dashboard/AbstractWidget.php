<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Core\View;

abstract class AbstractWidget
{
    protected $id;
    protected $title;

    public function getId(): string
    {
        return 'wpwps_' . $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function render(): void
    {
        View::render('admin.dashboard.widgets.' . $this->id, $this->getData());
    }

    abstract protected function getData(): array;
}
