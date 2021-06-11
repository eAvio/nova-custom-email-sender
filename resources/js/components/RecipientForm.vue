<template>
    <div class="mb-8">
        <div class="mb-6">
            <p class="mb-2">{{ messages["recipients-toggle-copy"] }}</p>
            <toggle-button
                :width="60"
                :height="26"
                color="var(--primary)"
                v-model="sendToAllInterface"
                :disabled="loading"
            />
        </div>
        <transition name="slide-fade">
            <div v-if="!sendToAllInterface">
                <p class="mb-2">
                    {{ messages["recipients-manual-input-copy"] }}
                </p>
                <form
                    id="email-search-form"
                    @submit.prevent="searchSubmit"
                    class="flex flex-wrap"
                >
                    <auto-complete-input
                        class="form-control flex-1"
                        name="search"
                        :loading.sync="loading"
                        :model.sync="search"
                        :results.sync="searchResults"
                        @search="performSearch"
                        @select="selectResult"
                        @ad-hoc="addAdHocEmail"
                        @paste="pasteAddresses"
                        :placeholder="
                            messages['recipients-manual-input-placeholder']
                        "
                        :messages="messages"
                    ></auto-complete-input>
                </form>
                <div class="my-2">
                    <p class="mb-2">{{ messages["select-user-groups"] }}</p>
                    <div id="group-box" class="flex flex-wrap">
                        <label
                            @click="selectGroup(group.id)"
                            class="btn group-button"
                            :class="[
                                isGroupSelected(group.id)
                                    ? 'group-selected'
                                    : 'group-unselected',
                            ]"
                            v-for="group in user_groups.filter(group => Object.keys(group.users).length > 0)"
                            :key="group.id"
                            >{{ group.name }}</label
                        >
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import EmailInputTag from "./EmailInputTag";
import AutoCompleteInput from "./AutoCompleteInput";
import { ToggleButton } from "vue-js-toggle-button";
import EmailUtility from "../services/EmailUtility";
import Recipient from "../interfaces/Recipient";

export default {
    name: "RecipientForm",
    components: {
        EmailInputTag,
        ToggleButton,
        AutoCompleteInput,
    },
    props: {
        messages: Object,
        sendToAll: {
            type: Boolean,
            default() {
                return false;
            },
        },
        loading: {
            type: Boolean,
            default() {
                return false;
            },
        },
        recipients: {
            type: Array,
            default() {
                return [];
            },
        },
    },
    data() {
        return {
            // loading: false,
            searchResults: [],
            search: "",
            user_groups: [],
            selected_groups: [],
        };
    },
    async mounted() {
        await this.getUserGroups();
    },
    computed: {
        sendToAllInterface: {
            get() {
                return this.sendToAll;
            },
            set(val) {
                this.$emit("update:send-to-all", val);
            },
        },
        recipientsInterface: {
            get() {
                return this.recipients;
            },
            set(val) {
                this.$emit("update:recipients", val);
            },
        },
    },
    methods: {
        getUserGroups() {
            Nova.request()
                .get("/nova-vendor/custom-email-sender/get-groups")
                .then((results) => {
                    // console.log(results);
                    this.user_groups = results.data;
                    // this.searchResults = results.data.map(result => {
                    //     return new Recipient(result.email, result.name)
                    // });
                    // this.loading = false;
                });
        },
        selectGroup(id) {
            let clickedGroup = this.user_groups.filter(function (group) {
                return group.id === id;
            })[0];

            if (this.isGroupSelected(id)) {
                this.selected_groups = this.selected_groups.filter(function (
                    group
                ) {
                    return group !== id;
                });

                this.$emit("removeGroup", clickedGroup.users);
            } else {
                this.selected_groups.push(id);
                this.$emit("addGroup", clickedGroup.users);
            }
        },

        isGroupSelected(id) {
            return this.selected_groups.includes(id);
        },
        validateEmailAddress() {
            return EmailUtility.validateEmailAddress(this.search);
        },

        addAdHocEmail() {
            if (
                this.searchResults.length > 0 ||
                this.validateEmailAddress() === false
            ) {
                return false;
            }

            this.$emit("add", {
                name: null,
                email: this.search,
            });

            this.search = "";
        },

        searchSubmit() {
            if (this.searchResults.length === 1) {
                this.$emit("add", this.searchResults[0]);
                this.searchResults.length = 0;
                this.search = "";
            } else {
                this.addAdHocEmail();
            }
        },

        performSearch($e) {
            Nova.request()
                .get("/nova-vendor/custom-email-sender/search", {
                    params: {
                        search: $e.query,
                    },
                    timeout: $e.timeout,
                })
                .then((results) => {
                    this.searchResults = results.data.map((result) => {
                        return new Recipient(result.email, result.name);
                    });
                    this.loading = false;
                });
        },

        selectResult(result) {
            for (let i = 0; i < this.searchResults.length; i++) {
                let target = this.searchResults[i];

                if (result.email === target.email) {
                    this.$emit("add", target);
                }
            }
        },

        pasteAddresses(event) {
            let pastedContent = (
                event.clipboardData || window.clipboardData
            ).getData("text");
            let pastedList = pastedContent.split(",");

            let validPaste = false;

            for (let i = 0; i < pastedList.length; i++) {
                let target = pastedList[i].trim();
                let addressExists = false;
                for (let ii = 0; i < this.recipients.length; ii++) {
                    if (target === this.recipients[ii].email) {
                        addressExists = true;
                        break;
                    }
                }
                if (
                    EmailUtility.validateEmailAddress(target) &&
                    !addressExists
                ) {
                    validPaste = true;
                    this.$emit("add", new Recipient(target));
                }
            }

            setTimeout(() => {
                if (validPaste) {
                    this.search = "";
                } else {
                    this.$toasted.show(this.messages["invalid-paste"], {
                        type: "error",
                    });
                }
                this.$forceUpdate();
            }, 100);
        },
    },
};
</script>

<style scoped>
.group-button {
    border: 1px solid lightgrey;
    border-radius: 0.5rem;
    padding: 0.25rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    margin: 0.25rem;
    transition: box-shadow 0.3s;
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* IE10+/Edge */
    user-select: none; /* Standard */
}
.group-button:hover {
    box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    transition: box-shadow 0.3s;
}
.group-selected {
    background-color: var(--primary);
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
    opacity: 0;
}
.slide-fade-enter-active {
    transition: all 0.3s ease;
}
.slide-fade-leave-active {
    transition: all 0.5s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter, .slide-fade-leave-to
        /* .slide-fade-leave-active below version 2.1.8 */ {
    transform: translateX(10px);
    opacity: 0;
}
</style>
