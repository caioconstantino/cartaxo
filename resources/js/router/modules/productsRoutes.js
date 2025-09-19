import ProductComponent from "../../components/admin/products/ProductComponent";
import ProductListComponent from "../../components/admin/products/ProductListComponent";
import ProductShowComponent from "../../components/admin/products/ProductShowComponent";

export default [
    {
        path: '/admin/products',
        component: ProductComponent,
        name: 'admin.products',
        redirect: { name: 'admin.products.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'products',
            breadcrumb: 'products'
        },
        children: [
            {
                path: '',
                component: ProductListComponent,
                name: 'admin.products.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'products',
                    breadcrumb: ''
                },
            },
            {
                path: "show/:id",
                component: ProductShowComponent,
                name: "admin.product.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "products",
                    breadcrumb: "view",
                },
            }
        ]
    }
]
