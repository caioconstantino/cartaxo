
import DamageComponent from '../../components/admin/damages/DamageComponent'
import DamageCreateAndEditComponent from '../../components/admin/damages/DamageCreateAndEditComponent'
import DamageListComponent from '../../components/admin/damages/DamageListComponent'
import DamageShowComponent from '../../components/admin/damages/DamageShowComponent'

export default [
    {
        path:'/admin/damages',
        component: DamageComponent,
        name: 'admin.damage',
        redirect: {name: 'admin.damage'},
        meta: {
            isFrontend:false,
            auth:true,
            permissionUrl: 'damages',
            breadcrumb:'damages'
        },
        children: [
            {
                path:'',
                component: DamageListComponent,
                name: 'admin.damage.list',
                meta: {
                    isFrontend:false,
                    auth:true,
                    permissionUrl: 'damages',
                    breadcrumb: ''
                }

            },
            {
                path: 'create',
                component: DamageCreateAndEditComponent,
                name: 'admin.return.create',
                meta: {
                    isFrontend:false,
                    auth:true,
                    permissionUrl: 'damage_create',
                    breadcrumb: 'create'
                }
            },
            {
                path: 'show/:id',
                component: DamageShowComponent,
                name: 'admin.damage.show',
                meta: {
                    isFrontend:false,
                    auth:true,
                    permissionUrl: 'damage_show',
                    breadcrumb: 'view'
                }
            },
            {
                path: 'edit/:id',
                component: DamageCreateAndEditComponent,
                name: 'admin.damage.edit',
                meta: {
                    isFrontend:false,
                    auth:true,
                    permissionUrl: 'damage_edit',
                    breadcrumb: 'edit'
                }
            }
        ]
    }
]