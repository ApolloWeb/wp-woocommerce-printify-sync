<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --wpwps-primary: #96588a;
        --wpwps-indigo: #4B0082;
        --wpwps-blue: #0066FF;
        --wpwps-teal: #008080;
        --wpwps-gray: #718096;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
    }
    
    .wpwps-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .wpwps-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
        background-color: var(--wpwps-primary);
        border-color: var(--wpwps-primary);
        border-radius: 50rem;
    }
    
    .wpwps-navbar {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.8);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .wpwps-sidebar {
        width: 250px;
        min-height: calc(100vh - 56px);
        background: rgba(255, 255, 255, 0.9);
        border-right: 1px solid rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
</style>