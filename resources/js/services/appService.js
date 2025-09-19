import store from "../store";
import statusEnum from "../enums/modules/statusEnum";
import purchasePaymentStatusEnum from "../enums/modules/purchasePaymentStatusEnum";
import purchaseStatusEnum from "../enums/modules/purchaseStatusEnum";
import askEnum from "../enums/modules/askEnum";
import taxTypeEnum from "../enums/modules/taxTypeEnum";
import orderStatusEnum from "../enums/modules/orderStatusEnum";
import returnStatusEnum from "../enums/modules/returnStatusEnum";
import VueSimpleAlert from "vue3-simple-alert";
import currencyPositionEnum from "../enums/modules/currencyPositionEnum";

export default {
    phoneNumber: function (e) {
        let char = String.fromCharCode(e.keyCode);
        if (/^[+]?[0-9]*$/.test(char)) return true;
        else e.preventDefault();
    },

    onlyNumber: function (e) {
        let res = (e.charCode !== 8 && e.charCode === 0 || (e.charCode >= 48 && e.charCode <= 57));
        if (res)
            return true;
        else
            e.preventDefault();
    },

    floatNumber: function (e) {
        let char = String.fromCharCode(e.keyCode);
        if (/^[.]?[0-9]*$/.test(char)) return true;
        else e.preventDefault();
    },

    currencyFormat(amount, decimal, currency, position) {
        if (position === currencyPositionEnum.LEFT) {
            return currency + parseFloat(amount).toFixed(decimal);
        } else {
            return parseFloat(amount).toFixed(decimal) + currency;
        }
    },

    floatFormat(amount, decimal = 2) {
        return parseFloat(amount).toFixed(decimal);
    },

    sideDrawerShow: function (id = 'sideDrawer') {
        const drawerDivs = document?.querySelectorAll(".drawer");
        const drawerSets = document?.querySelectorAll("[data-drawer]");
        drawerSets?.forEach((drawerSet) => {
            const targetElm = document?.querySelector(drawerSet?.dataset?.drawer);
            drawerSets?.forEach(drawerBtn => drawerBtn?.classList?.remove("active"));
            drawerDivs?.forEach(drawerDiv => drawerDiv?.classList?.remove("active"));
            targetElm?.classList?.add("active");
            drawerSet?.classList?.add("active");
            document.body.style.overflowY = "hidden";
            document?.querySelector(".backdrop")?.classList?.add("active");
        });
    },
    sideDrawerHide: function (id = 'sideDrawer') {
        const drawerDivs = document?.querySelectorAll(".drawer");
        const drawerSets = document?.querySelectorAll("[data-drawer]");
        document?.querySelectorAll("#sidebar")?.forEach((closeBtn) => {
            drawerSets?.forEach(drawerBtn => drawerBtn?.classList?.remove("active"));
            drawerDivs?.forEach(drawerDiv => drawerDiv?.classList?.remove("active"));
            document?.querySelector(".backdrop")?.classList?.remove("active");
            document.body.style.overflowY = "auto"
        });
    },

    modalShow: function (id = '.modal') {
        let modalButton = document?.querySelectorAll("[data-modal]");
        modalButton?.forEach((modalBtn) => {
            const modalTarget = document?.querySelector(id);
            modalTarget?.classList?.add("active");
            document.body.style.overflowY = "hidden";
        });
    },

    modalHide: function (id = ".modal") {
        let modalDivs = document?.querySelectorAll(id);
        document.body.style.overflowY = "auto";
        modalDivs?.forEach((modalDiv) => modalDiv?.classList?.remove("active"));
    },

    recursiveRouter: function (routes, permission) {
        let i, j;
        for (i = 0; i < routes.length; i++) {
            for (j = 0; j < permission.length; j++) {
                if (typeof routes[i].meta !== "undefined" && routes[i].meta) {
                    if (typeof routes[i].meta.permissionUrl !== "undefined" && routes[i].meta.permissionUrl) {
                        if (routes[i].meta.permissionUrl === permission[j].url) {
                            routes[i].meta.access = permission[j].access;
                            routes[i].meta.title = permission[j].title;
                        }

                        if (typeof routes[i].children !== "undefined" && routes[i].children) {
                            this.recursiveRouter(routes[i].children, permission);
                        }
                    }
                }
            }
        }
    },

    textShortener: function (text, number = 30) {
        if (text) {
            if (!(text.length < number)) {
                return text.substring(0, number) + "..";
            }
        }
        return text;
    },
    htmlTagRemover: function (text) {
        if ((text !=null && text !=='' && isNaN(text))) {
            return text.replace( /(<([^>]+)>)/ig, '');
        }
        return text;
    },
    statusClass: function (status) {
        if (status === statusEnum.ACTIVE) {
            return "db-table-badge text-green-600 bg-green-100";
        } else {
            return "db-table-badge text-red-600 bg-red-100";
        }
    },

    askClass: function (ask) {
        if (ask === askEnum.YES) {
            return "db-table-badge text-green-600 bg-green-100";
        } else {
            return "db-table-badge text-red-600 bg-red-100";
        }
    },

    requestHandler: function (requests) {
        let i = 1;
        let what = "?";
        let response = "";

        for (let request in requests) {
            if (requests[request] !== "" && requests[request] !== null) {
                if (i !== 1) {
                    response += "&";
                }
                response += request + "=" + requests[request];
            }
            i++;
        }

        if (response) {
            response = what + response;
        }

        return response;
    },

    responsiveLoad: function() {
        let mainHeader = document?.querySelector(".db-header");
        let subHeader = document?.querySelector(".sub-header");
        let mainHeight = mainHeader?.scrollHeight;

        if (subHeader) {
            subHeader.style.top = `${mainHeight}px`;
        }
    },

    permissionChecker: function (permissionName) {
        let i, permissions = store.getters.authPermission;
        for (i = 0; i < permissions.length; i++) {
            if (typeof permissions[i].name !== "undefined" && permissions[i].name) {
                if (permissions[i].name === permissionName) {
                    return permissions[i].access;
                }
            }
        }
    },

    formDataShow: function (formData) {
        for (let pair of formData.entries()) {
            console.log(pair[0] + " : " + pair[1]);
        }
    },
    destroyConfirmation: function () {
        return new VueSimpleAlert.confirm(
            "You will not be able to recover the deleted record!",
            "Are you sure?",
            "warning",
            {
                confirmButtonText: "Yes, Delete it!",
                cancelButtonText: "No, Cancel!",
                confirmButtonColor: "#696cff",
                cancelButtonColor: "#8592a3",
            }
        );
    },

    taxTypeClass: function (type) {
        if (type === taxTypeEnum.FIXED) {
            return "db-table-badge text-blue-500 bg-blue-100";
        } else {
            return "db-table-badge text-orange-500 bg-orange-100";
        }
    },
    underscoreToSpace: function (str) {
        return str.replace(/_/g, ' ');
    },
    decimalPoint: function (num,length=2) {
       return Number.parseFloat(num).toFixed(length);
    },
    orderStatusClass: function (status) {
        if(status === orderStatusEnum.PENDING){
           return "bg-amber-100 text-amber-500";
       }
       else if(status === orderStatusEnum.CONFIRMED){
           return "bg-indigo-100 text-indigo-500";
       }
       else if(status === orderStatusEnum.ON_THE_WAY){
           return "bg-cyan-100 text-cyan-500";
       }
       else if(status === orderStatusEnum.DELIVERED){
           return "bg-green-100 text-green-500";
       }
       else if(status === orderStatusEnum.CANCELED){
           return "bg-red-100 text-red-500";
       }
       else {
           return "bg-red-100 text-red-500";
       }
   },

   purchasePaymentStatusClass: function (status) {
        if(status === purchasePaymentStatusEnum.PENDING){
        return "bg-amber-100 text-amber-500";
        }
        else if(status === purchasePaymentStatusEnum.PARTIAL_PAID){
            return "bg-indigo-100 text-indigo-500";
        }
        else if(status === purchasePaymentStatusEnum.FULLY_PAID){
            return "bg-green-100 text-green-500";
        }
    },
    purchaseStatusClass: function (status) {
        if(status === purchaseStatusEnum.PENDING){
        return "bg-amber-100 text-amber-500";
        }
        else if(status === purchaseStatusEnum.ORDERED){
            return "bg-indigo-100 text-indigo-500";
        }
        else if(status === purchaseStatusEnum.RECEIVED){
            return "bg-green-100 text-green-500";
        }
    },

   returnStatusClass: function (status) {
        if(status == returnStatusEnum.PENDING){
        return "text-[#F6A609]";
        }
        else if(status == returnStatusEnum.ACCEPT){
            return "text-[#2AC769]";
        }
        else {
            return "text-[#FB4E4E]";
        }
    },

   cancelOrder: function () {
        return new VueSimpleAlert.confirm(
            "You want to cancel your order?",
            "Are you sure?",
            "warning",
                {
                    confirmButtonText: "Yes, Cancel it!",
                    cancelButtonText: "No, Cancel",
                    confirmButtonColor: "#696cff",
                    cancelButtonColor: "#8592a3",
                }
        );
    },

    acceptOrder: function () {
        return new VueSimpleAlert.confirm(
            "You will not be able to cancel the order!",
            "Are you sure?",
            "warning",
            {
                confirmButtonText: "Yes, Accept it!",
                cancelButtonText: "No, Cancel!",
                confirmButtonColor: "#696cff",
                cancelButtonColor: "#8592a3",
            }
        );
    },
};
