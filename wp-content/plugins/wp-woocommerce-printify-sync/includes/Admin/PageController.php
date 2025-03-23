// ...existing code...
protected function getTemplate(): string {
    return str_replace(
        '/home/apolloweb/projects/wp-woocommerce-printify-sync/',
        '/home/apolloweb/projects/wp-woocommerce-printify-sync/wp-content/plugins/wp-woocommerce-printify-sync/',
        $this->template
    );
}
// ...existing code...
