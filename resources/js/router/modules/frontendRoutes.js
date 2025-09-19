import HomeComponent from "../../components/frontend/home/HomeComponent";
import WishlistComponent from "../../components/frontend/wishlist/WishlistComponent";
import OrderHistoryComponent from "../../components/frontend/account/orderHistory/OrderHistoryComponent";
import ReturnOrdersComponent from "../../components/frontend/account/returnOrders/ReturnOrdersComponent";
import ReturnOrderDetailsComponent from "../../components/frontend/account/returnOrders/ReturnOrderDetailsComponent";
import ReturnOrderRequestComponent from "../../components/frontend/account/returnOrders/ReturnOrderRequestComponent";
import OrderDetailsComponent from "../../components/frontend/account/orderDetails/OrderDetailsComponent";
import ChangePasswordComponent from "../../components/frontend/account/changePassword/ChangePasswordComponent";
import AddressComponent from "../../components/frontend/account/address/AddressComponent";
import PageComponent from "../../components/frontend/page/PageComponent";
import ProductComponent from "../../components/frontend/product/ProductComponent";
import ProductDetailsComponent from "../../components/frontend/product/ProductDetailsComponent";
import PromotionProductComponent from "../../components/frontend/product/PromotionProductComponent";
import ProductSectionProductComponent from "../../components/frontend/product/ProductSectionProductComponent";
import FlashSaleProductComponent from "../../components/frontend/product/FlashSaleProductComponent";
import OfferProductComponent from "../../components/frontend/product/OfferProductComponent";
import OverviewComponent from "../../components/frontend/account/overview/OverviewComponent";
import AccountComponent from "../../components/frontend/account/AccountComponent";
import AccountInfoComponent from "../../components/frontend/account/accountInfo/AccountInfoComponent";
import CheckoutComponent from "../../components/frontend/checkout/CheckoutComponent";
import CheckoutCartListComponent from "../../components/frontend/checkout/cartList/CartListComponent";
import CartListHeaderComponent from "../../components/frontend/checkout/cartList/HeaderComponent";
import CheckoutCheckoutComponent from "../../components/frontend/checkout/checkout/CheckoutComponent";
import CheckoutHeaderComponent from "../../components/frontend/checkout/checkout/HeaderComponent";
import CheckoutPaymentComponent from "../../components/frontend/checkout/payment/PaymentComponent";
import PaymentHeaderComponent from "../../components/frontend/checkout/payment/HeaderComponent";
import ProductReviewComponent from "../../components/frontend/account/review/ProductReviewComponent";
import MostPopularProductComponent from "../../components/frontend/product/MostPopularProductComponent.vue";

export default [
    {
        path: "/home",
        component: HomeComponent,
        name: "frontend.home",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/product",
        component: ProductComponent,
        name: "frontend.product",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/product/:slug",
        component: ProductDetailsComponent,
        name: "frontend.product.details",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/offers",
        component: OfferProductComponent,
        name: "frontend.offers",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/promotion/:slug",
        component: PromotionProductComponent,
        name: "frontend.promotion.products",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/product-section/:slug",
        component: ProductSectionProductComponent,
        name: "frontend.productSection.products",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/most-popular",
        component: MostPopularProductComponent,
        name: "frontend.mostPopular.products",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },

    {
        path: "/flash-sale",
        component: FlashSaleProductComponent,
        name: "frontend.flashSale.products",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/wishlist",
        component: WishlistComponent,
        name: "frontend.wishlist",
        meta: {
            isFrontend: true,
            auth: true,
        },
    },
    {
        path: "/page/:slug",
        component: PageComponent,
        name: "frontend.page",
        meta: {
            isFrontend: true,
            auth: false,
        },
    },
    {
        path: "/account",
        component: AccountComponent,
        name: "frontend.account",
        redirect: {name: "frontend.account.overview"},
        meta: {
            isFrontend: true,
            auth: true,
        },
        children: [
            {
                path: "overview",
                component: OverviewComponent,
                name: "frontend.account.overview",
                meta: {
                    isFrontend: true,
                    auth: true
                }
            },
            {
                path: "order-history",
                component: OrderHistoryComponent,
                name: "frontend.account.orderHistory",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "return-orders",
                component: ReturnOrdersComponent,
                name: "frontend.account.returnOrders",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "return-order-details/:id",
                component: ReturnOrderDetailsComponent,
                name: "frontend.account.returnOrder.details",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "return-request/:id",
                component: ReturnOrderRequestComponent,
                name: "frontend.account.returnOrder.request",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "write-review/:slug",
                component: ProductReviewComponent,
                name: "frontend.account.productReview",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "edit-review/:slug/:id",
                component: ProductReviewComponent,
                name: "frontend.account.productReview.edit",
                meta: {
                    isFrontend: true,
                    auth: true,
                }
            },
            {
                path: "order-details/:id",
                component: OrderDetailsComponent,
                name: "frontend.account.orderDetails",
                meta: {
                    isFrontend: true,
                    auth: true,
                },
            },
            {
                path: "account-info",
                component: AccountInfoComponent,
                name: "frontend.account.accountInfo",
                meta: {
                    isFrontend: true,
                    auth: true,
                },
            },
            {
                path: "change-password",
                component: ChangePasswordComponent,
                name: "frontend.account.changePassword",
                meta: {
                    isFrontend: true,
                    auth: true,
                },
            },
            {
                path: "address",
                component: AddressComponent,
                name: "frontend.account.address",
                meta: {
                    isFrontend: true,
                    auth: true,
                },
            }
        ]
    },
    {
        path: "/checkout",
        component: CheckoutComponent,
        name: "frontend.checkout",
        redirect: {name: "frontend.checkout.checkout"},
        meta: {
            isFrontend: true,
            auth: true,
        },
        children: [
            {
                path: "cart-list",
                components: {default : CheckoutCartListComponent, header: CartListHeaderComponent},
                name: "frontend.checkout.cartList",
                meta: {
                    isFrontend: true,
                    auth: true
                }
            },
            {
                path: "checkout",
                components: {default: CheckoutCheckoutComponent, header: CheckoutHeaderComponent},
                name: "frontend.checkout.checkout",
                meta: {
                    isFrontend: true,
                    auth: true
                }
            },
            {
                path: "payment",
                components: {default: CheckoutPaymentComponent, header: PaymentHeaderComponent},
                name: "frontend.checkout.payment",
                meta: {
                    isFrontend: true,
                    auth: true
                }
            }
        ]
    }
];
