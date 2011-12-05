<?php

namespace YapepBase\Test\Mock\View;

use \YapepBase\View\Layout;

/**
 * Mock for a Layout.
 */
class LayoutMock extends Layout {
    /**
     * Render the fake content
     */
    protected function renderContent() {
        echo 'Layout: ' . $this->getTemplateOutput();
    }
}