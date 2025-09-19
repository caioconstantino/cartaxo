import PromotionComponent from "../../components/admin/promotions/PromotionComponent";
import PromotionListComponent from "../../components/admin/promotions/PromotionListComponent";
import PromotionShowComponent from "../../components/admin/promotions/PromotionShowComponent";

export default [
    {
        path: '/admin/promotions',
        component: PromotionComponent,
        name: 'admin.promotions',
        redirect: { name: 'admin.promotions.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'promotions',
            breadcrumb: 'promotions'
        },
        children: [
            {
                path: '',
                component: PromotionListComponent,
                name: 'admin.promotions.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'promotions',
                    breadcrumb: ''
                },
            },
            {
                path: "show/:id",
                component: PromotionShowComponent,
                name: "admin.promotion.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "promotions",
                    breadcrumb: "view",
                },
            },
        ]
    }
]
