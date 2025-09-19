<template>
    <div class="db-card-header">
        <h3 class="db-card-title">{{ $t('label.variation') }}</h3>
        <div class="db-card-filter">
            <ProductVariationCreateComponent :attributeProps="attributeProps" />
        </div>
    </div>
    <div class="db-card-body">
        <ul v-if="variations.length > 0" class="rounded overflow-hidden border border-gray-200">
            <li class="px-4 py-3 flex flex-wrap items-center gap-2.5 border-b last:border-none border-gray-200" v-for="variation in variations" :key="variation">
                <span class="flex-auto flex items-center">
                    <span v-for="(option, key) in variation.options" class="text-base font-medium capitalize tracking-wide whitespace-nowrap after:content-['\e037'] rtl:after:rotate-180 after:font-icon after:font-bold after:text-sm after:ml-1.5">
                        {{ key }} :: {{ option }}
                    </span>
                    <span class="text-base font-medium capitalize tracking-wide whitespace-nowrap after:content-['\e037'] rtl:after:rotate-180 after:font-icon after:font-bold after:text-sm after:ml-1.5">
                        {{ $t('label.price') }} :: {{ variation.price }}
                    </span>
                    <div  class="flex-auto flex sm:justify-end gap-2">
                        <SmIconModalEditComponent @click="editVariation(variation.id)" />
                        <SmIconDeleteComponent @click="destroyVariation(variation.id)" />
                    </div>
                </span>
            </li>
        </ul>
    </div>
</template>

<script>
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";
import SmIconModalEditComponent from "../../components/buttons/SmIconModalEditComponent.vue";
import SmIconDeleteComponent from "../../components/buttons/SmIconDeleteComponent.vue";
import ProductVariationCreateComponent from "./ProductVariationCreateComponent";
import _ from "lodash";

export default {
    name: "ProductVariationListComponent",
    components: {ProductVariationCreateComponent, SmIconDeleteComponent, SmIconModalEditComponent},
    data() {
        return {
            loading: {
                isActive: false
            },
            productId : null,
            attributeProps: {
                price: null,
                sku: null,
                elements: {},
                attribute: []
            },
        }
    },
    computed: {
        variations: function () {
            return this.$store.getters['productVariation/singleTree'];
        }
    },
    mounted() {
        try {
            this.loading.isActive = true;
            this.productId = this.$route.params.id;
            this.$store.dispatch('productVariation/singleTree', this.productId).then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response.data.message);
            });
        } catch (err) {
            this.loading.isActive = false;
            alertService.error(err.response.data.message);
        }
    },
    methods : {
        editVariation: function (productVariation) {
            appService.modalShow('#variationModal');
            this.loading.isActive = true;
            this.$store.dispatch('productVariation/edit', {
                productId: this.productId,
                id: productVariation
            }).then((res) => {
                this.loading.isActive = false;
                _.forEach(res.data.data, (element) => {
                    this.recursiveVariation(element);
                });
            }).catch((err) => {
                this.loading.isActive = false;
            })
        },
        destroyVariation: function (id) {
            appService.destroyConfirmation().then(res => {
                try {
                    this.loading.isActive = true;
                    this.$store.dispatch('productVariation/destroy', {
                        productVariationId: id,
                        productId: this.productId
                    }).then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(null, this.$t('label.variation'));
                    }).catch((err) => {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    })
                } catch (err) {
                    this.loading.isActive = false;
                    alertService.error(err.response.data.message);
                }
            }).catch((err) => {
                this.loading.isActive = false;
            })
        },
        recursiveVariation: function (data) {
            this.attributeProps.elements[data.product_attribute_id] = data.product_attribute_option_id;
            if (data.sku !== null) {
                this.attributeProps.price = data.price;
                this.attributeProps.sku = data.sku;
            }
            if (data.children) {
                _.forEach(data.children, (element) => {
                    this.recursiveVariation(element);
                });
            }
        },
    }
}
</script>
