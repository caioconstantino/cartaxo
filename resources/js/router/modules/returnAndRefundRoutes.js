import ReturnAndRefundComponent from "../../components/admin/returnAndRefunds/ReturnAndRefundComponent";
import ReturnAndRefundListComponent from "../../components/admin/returnAndRefunds/ReturnAndRefundListComponent";
import ReturnAndRefundShowComponent from "../../components/admin/returnAndRefunds/ReturnAndRefundShowComponent";

export default [
    {
        path: '/admin/return-and-refunds',
        component: ReturnAndRefundComponent,
        name: 'admin.returnAndRefund',
        redirect: {name: 'admin.returnAndRefund.list'},
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'return-and-refunds',
            breadcrumb: 'return_and_refunds'
        },
        children: [
            {
                path: '',
                component: ReturnAndRefundListComponent,
                name: 'admin.returnAndRefund.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'return-and-refunds',
                    breadcrumb: ''
                },
            },
            {
                path: "show/:id",
                component: ReturnAndRefundShowComponent,
                name: "admin.returnAndRefund.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "return-and-refunds",
                    breadcrumb: "view",
                },
            }
        ]
    }
]
