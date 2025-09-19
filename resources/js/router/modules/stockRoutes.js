import StockComponent from "../../components/admin/stock/StockComponent";
import StockListComponent from "../../components/admin/stock/StockListComponent";

export default [
    {
        path: '/admin/stock',
        component: StockComponent,
        name: 'admin.stock',
        redirect: { name: 'admin.stock.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'stock',
            breadcrumb: 'stock'
        },
        children: [
            {
                path: '',
                component: StockListComponent,
                name: 'admin.stock.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'stock',
                    breadcrumb: ''
                },
            }
        ]
    }
]
