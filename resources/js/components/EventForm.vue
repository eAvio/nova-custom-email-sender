<template>
  <div class="mb-8">
    <div class="mb-4">
      <p class="mb-2">{{ messages["event-toggle-create"] }}</p>
      <toggle-button
        :width="60"
        :height="26"
        color="var(--primary)"
        v-model="createCalendarEvent"
        :disabled="loading"
      />
    </div>
    <transition name="slide-fade">
      <div v-if="createCalendarEvent">
        <p class="mb-4">
          {{ messages["event-details"] }}
        </p>
        <form
          id="email-search-form"
          @submit.prevent="searchSubmit"
          class="flex flex-wrap"
        >
          <div class="w-full">
            <h3 slot="default" class="text-sm tracking-wide text-80">
              {{ messages["event-title"] }}
            </h3>
            <div class="py-2 pr-2">
              <input
                v-model="eventTitle"
                @change="changeData()"
                class="block w-full form-control-sm form-input form-input-bordered"
              />
            </div>
          </div>

          <!-- <div class="w-1/4">
            <h3 slot="default" class="text-sm tracking-wide text-80">
              {{ messages["event-full-day"] }}
            </h3>
            <toggle-button
              class="py-2"
              :width="60"
              :height="26"
              color="var(--primary)"
              v-model="eventFullDay"
              :disabled="loading"
              @change="changeData()"
            />
          </div> -->

          <div class="w-1/2" v-if="!eventFullDay">
            <h3 slot="default" class="text-sm tracking-wide text-80">
              {{ messages["event-date-from"] }}
            </h3>
            <div class="py-2 mr-2">
              <flat-pickr
                v-model="eventDateFrom"
                class="block w-full form-control-sm form-select"
                :config="config"
                @input="changeData()"
              ></flat-pickr>
            </div>
          </div>

          <div class="w-1/2" v-if="!eventFullDay">
            <h3 slot="default" class="text-sm tracking-wide text-80">
              {{ messages["event-date-to"] }}
            </h3>
            <div class="py-2">
              <flat-pickr
                v-model="eventDateTo"
                class="block w-full form-control-sm form-select"
                :config="config"
                @input="changeData()"
              ></flat-pickr>
            </div>
          </div>

          <div class="w-full">
            <h3 slot="default" class="text-sm tracking-wide text-80">
              {{ messages["event-description"] }}
            </h3>
            <div class="py-2">
              <textarea
                v-model="eventDescription"
                rows="4"
                @change="changeData()"
                class="block w-full form-input-bordered py-2"
              ></textarea>
            </div>
          </div>
        </form>
      </div>
    </transition>
  </div>
</template>

<script>
import EmailInputTag from "./EmailInputTag";
import AutoCompleteInput from "./AutoCompleteInput";
import { ToggleButton } from "vue-js-toggle-button";

import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";

export default {
  name: "EventForm",
  components: {
    flatPickr,
    EmailInputTag,
    ToggleButton,
    AutoCompleteInput,
  },
  props: {
    messages: Object,
  },
  data() {
    return {
      config: {
        dateFormat: "d.m.Y H:i",
        enableTime: true,
        allowInput: true,
      },
      eventTitle: "",
      eventDescription: "",
      eventFullDay: false,
      eventDateFrom: null,
      eventDateTo: null,
      createEvent: false,
    };
  },
  async mounted() {},
  computed: {
    createCalendarEvent: {
      get() {
        return this.createEvent;
      },
      set(val) {
        this.createEvent = val;
        this.$emit("createEventChange", val);
      },
    },
  },
  methods: {
    changeData() {
      this.$emit("changeData", {
        eventTitle: this.eventTitle,
        eventDescription: this.eventDescription,
        eventFullDay: this.eventFullDay,
        eventDateFrom: this.eventDateFrom,
        eventDateTo: this.eventDateTo,
      });
    },
  },
};
</script>

<style scoped>
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
