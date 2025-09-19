<template>
  <LoadingComponent :props="loading" />
  <div class="col-12">
    <div class="db-card">
      <div class="db-card-header border-none">
        <h3 class="db-card-title">{{ $t("menu.online_orders") }}</h3>
        <div class="db-card-filter">
          <TableLimitComponent
            :method="list"
            :search="props.search"
            :page="paginationPage"
          />
          <FilterComponent />
          <div class="dropdown-group">
            <ExportComponent />
            <div class="dropdown-list db-card-filter-dropdown-list">
              <PrintComponent :props="printObj" />
              <ExcelComponent :method="xls" />
            </div>
          </div>
          <button
            type="button"
            @click="generateTagsCorreio"
            class="flex items-center justify-center text-white gap-2 px-4 h-[38px] rounded shadow-db-card bg-[#2AC769]"
          >
            <span class="text-sm capitalize text-white"
              >Imprimir Etiquetas</span
            >
          </button>
        </div>
      </div>

      <div class="table-filter-div">
        <form class="p-4 sm:p-5 mb-5 w-full d-block" @submit.prevent="search">
          <div class="row">
            <div class="col-12 sm:col-6 md:col-4 xl:col-3">
              <label for="order_id" class="db-field-title after:hidden">{{
                $t("label.order_id")
              }}</label>
              <input
                id="order_id"
                v-model="props.search.order_serial_no"
                type="text"
                class="db-field-control"
              />
            </div>

            <div class="col-12 sm:col-6 md:col-4 xl:col-3">
              <label for="searchStatus" class="db-field-title after:hidden">
                {{ $t("label.status") }}
              </label>
              <vue-select
                class="db-field-control f-b-custom-select"
                id="searchStatus"
                v-model="props.search.status"
                :options="[
                  {
                    id: enums.orderStatusEnum.PENDING,
                    name: $t('label.pending'),
                  },
                  {
                    id: enums.orderStatusEnum.CONFIRMED,
                    name: $t('label.confirmed'),
                  },
                  {
                    id: enums.orderStatusEnum.ON_THE_WAY,
                    name: $t('label.on_the_way'),
                  },
                  {
                    id: enums.orderStatusEnum.DELIVERED,
                    name: $t('label.delivered'),
                  },
                  {
                    id: enums.orderStatusEnum.CANCELED,
                    name: $t('label.canceled'),
                  },
                  {
                    id: enums.orderStatusEnum.REJECTED,
                    name: $t('label.rejected'),
                  },
                ]"
                label-by="name"
                value-by="id"
                :closeOnSelect="true"
                :searchable="true"
                :clearOnClose="true"
                placeholder="--"
                search-placeholder="--"
              />
            </div>

            <div class="col-12 sm:col-6 md:col-4 xl:col-3">
              <label for="user_id" class="db-field-title">
                {{ $t("label.customer") }}
              </label>
              <vue-select
                class="db-field-control f-b-custom-select"
                id="user_id"
                v-model="props.search.user_id"
                :options="customers"
                label-by="name"
                value-by="id"
                :closeOnSelect="true"
                :searchable="true"
                :clearOnClose="true"
                placeholder="--"
                search-placeholder="--"
              />
            </div>

            <div class="col-12 sm:col-6 md:col-4 xl:col-3">
              <label for="searchStartDate" class="db-field-title after:hidden">
                {{ $t("label.date") }}
              </label>
              <Datepicker
                hideInputIcon
                autoApply
                :enableTimePicker="false"
                utc="false"
                @update:modelValue="handleDate"
                v-model="modelValue"
                :range="true"
              >
              </Datepicker>
            </div>

            <div class="col-12">
              <div class="flex flex-wrap gap-3 mt-4">
                <button class="db-btn py-2 text-white bg-primary">
                  <i class="lab lab-line-search lab-font-size-16"></i>
                  <span>{{ $t("button.search") }}</span>
                </button>
                <button
                  class="db-btn py-2 text-white bg-gray-600"
                  @click="clear"
                >
                  <i class="lab lab-line-cross lab-font-size-22"></i>
                  <span>{{ $t("button.clear") }}</span>
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="db-table-responsive">
        <table class="db-table stripe" id="print">
          <thead class="db-table-head">
            <tr class="db-table-head-tr">
              <th class="db-table-head-th">{{ $t("label.order_id") }}</th>
              <th class="db-table-head-th">{{ $t("label.order_type") }}</th>
              <th class="db-table-head-th">{{ $t("label.customer") }}</th>
              <th class="db-table-head-th">{{ $t("label.amount") }}</th>
              <th class="db-table-head-th">{{ $t("label.date") }}</th>
              <th class="db-table-head-th">{{ $t("label.status") }}</th>
              <th
                class="db-table-head-th hidden-print"
                v-if="permissionChecker('online-orders')"
              >
                {{ $t("label.action") }}
              </th>
            </tr>
          </thead>
          <tbody class="db-table-body" v-if="orders.length > 0">
            <tr class="db-table-body-tr" v-for="order in orders" :key="order">
              <td class="db-table-body-td">
                {{ order.order_serial_no }}
              </td>
              <td class="db-table-body-td">
                <span :class="statusClass(order.order_type)">
                  {{ enums.orderTypeEnumArray[order.order_type] }}
                </span>
              </td>

              <td class="db-table-body-td">
                {{ textShortener(order.user.name, 20) }}
              </td>
              <td class="db-table-body-td">{{ order.total_amount_price }}</td>
              <td class="db-table-body-td">
                {{ order.order_datetime }}
              </td>
              <td class="db-table-body-td">
                <span
                  class="db-table-badge"
                  :class="orderStatusClass(order.status)"
                >
                  {{ enums.orderStatusEnumArray[order.status] }}
                </span>
              </td>
              <td
                class="db-table-body-td hidden-print"
                v-if="permissionChecker('online-orders')"
              >
                <div
                  class="flex justify-start items-center sm:items-start sm:justify-start gap-1.5"
                >
                  <SmIconViewComponent
                    :link="'admin.order.show'"
                    :id="order.id"
                    v-if="permissionChecker('online-orders')"
                  />

                  <button
                    v-if="order.idRecibo !== null && order.idRecibo !== ''"
                    class="px-2 py-1 bg-blue-500 text-white rounded"
                    @click="baixarEtiqueta(order.idRecibo)"
                  >
                    BAIXAR ETQ
                  </button>

                  <button
                    v-if="order.nfe_id !== null && order.nfe_id !== ''"
                    class="px-2 py-1 bg-red-500 text-white rounded"
                    @click="pdfNfe(order.nfe_id)"
                  >
                    NFE
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6"
      >
        <PaginationSMBox :pagination="pagination" :method="list" />
        <div
          class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
        >
          <PaginationTextComponent :props="{ page: paginationPage }" />
          <PaginationBox :pagination="pagination" :method="list" />
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import PaginationTextComponent from "../components/pagination/PaginationTextComponent";
import PaginationBox from "../components/pagination/PaginationBox";
import PaginationSMBox from "../components/pagination/PaginationSMBox";
import appService from "../../../services/appService";
import orderStatusEnum from "../../../enums/modules/orderStatusEnum";
import orderTypeEnum from "../../../enums/modules/orderTypeEnum";
import TableLimitComponent from "../components/TableLimitComponent";
import SmIconDeleteComponent from "../components/buttons/SmIconDeleteComponent";
import SmIconViewComponent from "../components/buttons/SmIconViewComponent";
import FilterComponent from "../components/buttons/collapse/FilterComponent";
import ExportComponent from "../components/buttons/export/ExportComponent";
import PrintComponent from "../components/buttons/export/PrintComponent";
import ExcelComponent from "../components/buttons/export/ExcelComponent";
import statusEnum from "../../../enums/modules/statusEnum";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import axios from "axios";

export default {
  name: "OnlineOrderListComponent",
  components: {
    TableLimitComponent,
    PaginationSMBox,
    PaginationBox,
    PaginationTextComponent,
    LoadingComponent,
    SmIconDeleteComponent,
    SmIconViewComponent,
    FilterComponent,
    ExportComponent,
    PrintComponent,
    ExcelComponent,
    Datepicker,
  },
  data() {
    return {
      loading: {
        isActive: false,
      },
      enums: {
        orderStatusEnum: orderStatusEnum,
        orderTypeEnum: orderTypeEnum,
        orderStatusEnumArray: {
          [orderStatusEnum.PENDING]: this.$t("label.pending"),
          [orderStatusEnum.CONFIRMED]: this.$t("label.confirmed"),
          [orderStatusEnum.ON_THE_WAY]: this.$t("label.on_the_way"),
          [orderStatusEnum.DELIVERED]: this.$t("label.delivered"),
          [orderStatusEnum.CANCELED]: this.$t("label.canceled"),
          [orderStatusEnum.REJECTED]: this.$t("label.rejected"),
        },
        orderTypeEnumArray: {
          [orderTypeEnum.DELIVERY]: this.$t("label.delivery"),
          [orderTypeEnum.PICK_UP]: this.$t("label.pick_up"),
        },
      },
      printLoading: true,
      printObj: {
        id: "print",
        popTitle: this.$t("menu.online_orders"),
      },
      props: {
        search: {
          paginate: 1,
          page: 1,
          per_page: 10,
          order_column: "id",
          order_by: "desc",
          order_serial_no: "",
          user_id: null,
          excepts: orderTypeEnum.POS,
          status: null,
          active: statusEnum.ACTIVE,
          from_date: "",
          to_date: "",
        },
      },
      modelValue: null,
    };
  },
  mounted() {
    this.list();
    this.$store.dispatch("user/lists", {
      order_column: "id",
      order_type: "asc",
      status: statusEnum.ACTIVE,
    });
  },
  computed: {
    orders: function () {
      return this.$store.getters["onlineOrder/lists"];
    },
    customers: function () {
      return this.$store.getters["user/lists"];
    },
    pagination: function () {
      return this.$store.getters["onlineOrder/pagination"];
    },
    paginationPage: function () {
      return this.$store.getters["onlineOrder/page"];
    },
  },
  methods: {
    async generateTagsCorreio() {
      this.loading = true; // Mostra "Gerando rótulo..."

      try {
        const confirmedOrders = this.orders.filter(
          (order) => order.status === this.enums.orderStatusEnum.CONFIRMED
        );

        if (confirmedOrders.length === 0) {
          console.log("Nenhum pedido confirmado encontrado.");
          return;
        }

        console.log("Pedidos confirmados:", confirmedOrders);

        const response = await axios.post("correio/gera-etiqueta", {
          confirmedOrders,
        });

        const apiResponse = response.data;
        console.log("Resposta da API:", apiResponse);

        if (apiResponse.status === "sucesso") {
          console.log(
            "Rótulos gerados e pedidos atualizados!",
            apiResponse.orders
          );
        } else {
          console.error("Erro ao gerar etiquetas:", apiResponse.message);
        }
      } catch (error) {
        console.error(
          "Erro ao gerar etiquetas:",
          error.response?.data || error.message
        );
      } finally {
        this.loading = false; // esconde o indicador
      }
    },

    async baixarEtiqueta(idRecibo) {
      try {
        const response = await axios.get(
          `/correio/download-etiqueta/${idRecibo}`,
          {
            responseType: "blob", // importante
          }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "etiqueta.pdf");
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error("Erro ao baixar etiqueta:", error);
      }
    },
    async updateOrdersWithShippingData(orderList) {
      this.loading.isActive = true;

      // Garante que os dados sejam enviados no formato esperado pelo backend
      const payload = { orders: orderList };

      // Enviar os dados via Vuex primeiro
      try {
        await this.$store.dispatch("onlineOrder/updateShippingInfos", payload);
        console.log("Pedidos atualizados via Vuex.");
      } catch (err) {
        console.error("Erro ao atualizar pedidos via Vuex:", err);
      } finally {
        this.loading.isActive = false;
      }

      // Se necessário, enviar uma requisição fetch também
    },
    async pdfNfe(idNfe) {
      try {
        const response = await axios.get(
          `/bling/pdfNfe/${idNfe}`,
          {
            responseType: "json", // importante
          }
        );

        
      } catch (error) {
        console.error("Erro ao baixar etiqueta:", error);
      }
    },
    permissionChecker(e) {
      return appService.permissionChecker(e);
    },
    statusClass: function (status) {
      return appService.statusClass(status);
    },
    orderStatusClass: function (status) {
      return appService.orderStatusClass(status);
    },
    textShortener: function (text, number = 30) {
      return appService.textShortener(text, number);
    },
    search: function () {
      this.list();
    },
    handleDate: function (e) {
      if (e) {
        this.props.search.from_date = e[0];
        this.props.search.to_date = e[1];
      } else {
        this.props.search.from_date = null;
        this.props.search.to_date = null;
      }
    },
    clear: function () {
      this.props.search.paginate = 1;
      this.props.search.page = 1;
      this.props.search.order_by = "desc";
      this.props.search.order_serial_no = "";
      this.props.search.status = null;
      this.props.search.excepts = orderTypeEnum.POS;
      this.props.search.from_date = null;
      this.props.search.to_date = null;
      this.props.search.user_id = null;
      this.modelValue = null;
      this.list();
    },
    list: function (page = 1) {
      this.loading.isActive = true;
      this.props.search.page = page;
      this.$store
        .dispatch("onlineOrder/lists", this.props.search)
        .then((res) => {
          this.loading.isActive = false;
        })
        .catch((err) => {
          this.loading.isActive = false;
        });
    },
    xls: function () {
      this.loading.isActive = true;
      this.$store
        .dispatch("onlineOrder/export", this.props.search)
        .then((res) => {
          this.loading.isActive = false;
          const blob = new Blob([res.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          });
          const link = document.createElement("a");
          link.href = URL.createObjectURL(blob);
          link.download = this.$t("menu.online_orders");
          link.click();
          URL.revokeObjectURL(link.href);
        })
        .catch((err) => {
          this.loading.isActive = false;
          alertService.error(err.response.data.message);
        });
    },
  },
};
</script>

<style scoped>
.dp__main:has(.box) .dp__input {
  border-color: inherit;
}

@media print {
  .hidden-print {
    display: none !important;
  }
}
</style>
