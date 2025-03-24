<?php
// ...existing code...

    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render() {
        // Get dashboard data
        $data = $this->getDashboardData();
        
        // Render template
        $this->template->render('wpwps-admin/dashboard', $data);
    }
    
    // ...existing code...
