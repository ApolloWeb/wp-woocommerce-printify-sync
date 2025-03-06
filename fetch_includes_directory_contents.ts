{
  "task": "fetch_includes_directory_contents",
  "steps": [
    {
      "name": "fetch_root_includes_directory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes",
        "endpointDescription": "list contents of includes directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    },
    {
      "name": "fetch_subdirectory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes/API",
        "endpointDescription": "list contents of includes/API directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    },
    {
      "name": "fetch_subdirectory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes/Email",
        "endpointDescription": "list contents of includes/Email directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    },
    {
      "name": "fetch_subdirectory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes/Install",
        "endpointDescription": "list contents of includes/Install directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    },
    {
      "name": "fetch_subdirectory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes/Processing",
        "endpointDescription": "list contents of includes/Processing directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    },
    {
      "name": "fetch_subdirectory",
      "action": "get-github-data",
      "parameters": {
        "endpoint": "/repos/ApolloWeb/wp-woocommerce-printify-sync/contents/includes/Utility",
        "endpointDescription": "list contents of includes/Utility directory",
        "repo": "ApolloWeb/wp-woocommerce-printify-sync"
      }
    }
  ]
}