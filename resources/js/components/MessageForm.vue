<template>
  <div class="flex">
    <script
      type="application/javascript"
      defer
      src="https://unpkg.com/vue-upload-component"
    ></script>
    <script
      type="application/javascript"
      defer
      src="https://unpkg.com/vue"
    ></script>
    <div class="w-3/5">
      <h3 class="text-base text-80 font-bold mb-3">
        {{ messages["from-header"] }}
      </h3>

      <div class="mb-8">
        <p class="mb-2 italic">{{ messages["from-copy"] }}</p>
        <select-control
          v-model="from"
          class="w-full form-control form-select"
          :disabled="config.from.options.length <= 1 || isThinking"
        >
          <option value="" selected disabled>
            {{ messages["choose-an-option"] }}
          </option>
          <option
            v-for="option in config.from.options"
            :key="option.address"
            :value="option.address"
          >
            {{ option.name }}
          </option>
        </select-control>
      </div>

      <div class="mb-8">
        <h3 class="text-base text-80 font-bold mb-3">
          {{ messages["subject-header"] }}
        </h3>
        <div class="mb-8">
          <p class="mb-2 italic">{{ messages["subject-copy"] }}</p>
          <counter-input
            :placeholder="messages['subject-placeholder']"
            :model.sync="subject"
            :disabled="isThinking"
          ></counter-input>
        </div>
      </div>

      <div class="mb-8">
        <h3 class="text-base text-80 font-bold mb-3">
          {{ messages["recipients-header"] }}
        </h3>
        <recipient-form
          :messages="messages"
          @add="addAddress"
          @addGroup="addGroup"
          @removeGroup="removeGroup"
          :send-to-all.sync="sendToAll"
          :loading="isThinking"
          :recipients="recipients"
        ></recipient-form>
      </div>

      <div class="mb-8">
        <h3 class="text-base text-80 font-bold mb-3">
          {{ messages["event-header"] }}
        </h3>
        <event-form
          :messages="messages"
          @createEventChange="createEventChange"
          @changeData="changeEventData"
        ></event-form>
      </div>

      <div class="mb-8">
        <h3 class="text-base text-80 font-bold mb-3">
          {{ messages["content-header"] }}
        </h3>

        <div class="mb-6">
          <p class="mb-2">{{ messages["toggle-use-file"] }}</p>
          <toggle-button
            :width="60"
            :height="26"
            color="var(--primary)"
            v-model="useFileContent"
            :disabled="loading"
          />
        </div>
        <div class="mb-8" v-if="useFileContent">
          <file-select
            @input="loadFile"
            v-model="htmlFile"
            :messages="messages"
          />
        </div>
        <div class="mb-8" v-else>
          <p class="mb-2">{{ messages["content-copy"] }}</p>
          <div class="input-wrapper">
            <quill-editor
              class="quill-editor"
              :options="quillEditorOptions"
              v-model="htmlContent"
              ref="myQuillEditor"
            ></quill-editor>
          </div>
          <div
            class="drop-class"
            :class="
              $refs.upload && $refs.upload.dropActive ? 'upload-highlight' : ''
            "
          >
            <file-upload
              v-if="!loading"
              class="file-upload-field"
              ref="upload"
              v-model="files"
              put-action="/upload/addFiles"
              drop=".drop-class"
              :headers="{ 'X-CSRF-Token': token }"
              :multiple="true"
              :size="1024 * 1024 * 10"
              :maximum="10"
              @input-file="inputFile"
              @input-filter="inputFilter"
            >
              Add Attachment
            </file-upload>
            <ul>
              <li
                class="attachment-item"
                v-for="(file, index) in files"
                :key="file.id"
              >
                <img v-if="isImage(file.name)" :src="file.blob" />
                <img
                  v-else
                  src="https://findicons.com/files/icons/1579/devine/256/file.png"
                />
                <span class="text-wrapper"
                  ><span class="file-name">{{ file.name }}</span>
                  <!-- <span class="errors"
                                        >Error: {{ file.error }}, Success:
                                        {{ file.success }}</span
                                    > -->
                </span>
                <span
                  class="status"
                  :class="file.success == true ? 'success' : 'fail'"
                ></span>
                <span class="trash-box" @click="removeFile(index)">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                  >
                    <path
                      d="M3 6v18h18v-18h-18zm5 14c0 .552-.448 1-1 1s-1-.448-1-1v-10c0-.552.448-1 1-1s1 .448 1 1v10zm5 0c0 .552-.448 1-1 1s-1-.448-1-1v-10c0-.552.448-1 1-1s1 .448 1 1v10zm5 0c0 .552-.448 1-1 1s-1-.448-1-1v-10c0-.552.448-1 1-1s1 .448 1 1v10zm4-18v2h-20v-2h5.711c.9 0 1.631-1.099 1.631-2h5.315c0 .901.73 2 1.631 2h5.712z"
                    />
                  </svg>
                </span>
              </li>
            </ul>

            <div
              class="drop-here-label"
              v-if="$refs.upload && $refs.upload.dropActive"
            >
              Drop here...
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <div v-if="nebulaSenderActive">
          <div v-if="existingMessage">
            <h3 class="text-base text-80 font-bold mb-3">
              {{ messages["send-preview-update-draft"] }}
            </h3>
            <p class="mb-2">
              {{ messages["preview-update-draft-copy"] }}
            </p>
          </div>
          <div v-else>
            <h3 class="text-base text-80 font-bold mb-3">
              {{ messages["send-preview-draft"] }}
            </h3>
            <p class="mb-2">{{ messages["preview-draft-copy"] }}</p>
          </div>
        </div>
        <div v-else>
          <h3 class="text-base text-80 font-bold mb-3">
            {{ messages["send-preview"] }}
          </h3>
          <p class="mb-2">{{ messages["preview-copy"] }}</p>
        </div>

        <div class="flex" v-if="nebulaSenderActive">
          <div class="flex-1">
            <button
              class="btn btn-default btn-primary"
              @click="sendMessage"
              :disabled="isThinking || !formIsValid()"
            >
              {{
                loading
                  ? messages["send-message-loading"]
                  : messages["send-message"]
              }}
            </button>
            <button
              class="btn btn-default btn-primary"
              @click="saveDraft"
              :disabled="isThinking || !draftIsValid()"
            >
              <span v-if="draftSaved">
                {{
                  draftSaving ? messages["updating"] : messages["update-draft"]
                }}
              </span>
              <span v-else>
                {{ draftSaving ? messages["saving"] : messages["save-draft"] }}
              </span>
            </button>
          </div>
          <div class="text-right">
            <button
              class="btn btn-default btn-secondary"
              @click="preview"
              :disabled="isThinking || !formIsValid()"
            >
              {{
                gettingPreview
                  ? messages["preview-loading"]
                  : messages["preview"]
              }}
            </button>
          </div>
        </div>
        <div v-else>
          <button
            class="btn btn-default btn-primary"
            @click="sendMessage"
            :disabled="isThinking || !formIsValid()"
          >
            {{
              loading
                ? messages["send-message-loading"]
                : messages["send-message"]
            }}
          </button>
          <button
            class="btn btn-default btn-secondary"
            @click="preview"
            :disabled="isThinking || !formIsValid()"
          >
            {{
              gettingPreview ? messages["preview-loading"] : messages["preview"]
            }}
          </button>
        </div>
      </div>
    </div>

    <div class="w-2/5">
      <div class="recipients-list px-6">
        <h3 class="text-base text-80 font-bold mb-3">
          {{ messages["recipients-list-header"] }}
          {{ !sendToAll ? `(${recipients.length})` : "" }}
        </h3>
        <div>
          <ul class="divide-y divide-gray-200" style="padding-left: 0">
            <recipient-item
              :recipient="recipient"
              v-for="(recipient, index) of recipients"
              :key="index"
              @delete="removeRecipient(index)"
            ></recipient-item>
          </ul>
        </div>

        <div
          v-if="
            (!recipients && sendToAll === false) ||
            (recipients.length === 0 && sendToAll === false)
          "
          class="relative rounded-md p-4 overflow-hidden"
        >
          <div
            class="absolute w-full h-full bg-danger opacity-25"
            style="left: 0; top: 0"
          ></div>
          <div class="relative flex">
            <div class="flex-shrink-0">
              <svg
                class="h-5 w-5 text-danger"
                x-description="Heroicon name: x-circle"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                ></path>
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm leading-5 font-medium text-danger">
                {{ messages["recipients-no-address-found"] }}
              </h3>
            </div>
          </div>
        </div>
        <div v-if="sendToAll === true" class="p-4 bg-primary rounded">
          <p class="text-white">
            {{ messages["recipients-send-all"] }}
          </p>
        </div>
      </div>
    </div>

    <preview-modal
      ref="previewModal"
      @preview="setGettingPreview"
    ></preview-modal>
  </div>
</template>

<script src="../../../node_modules/quill-image-resize-module/image-resize.min.js"></script>
<script>
import "quill/dist/quill.core.css";
import "quill/dist/quill.snow.css";
import "quill/dist/quill.bubble.css";

import Quill from "quill";

window.Quill = Quill;

// const ImageResize = require('quill-image-resize-module').default
// Quill.register('modules/imageResize', ImageResize);

import { ImageDrop } from "quill-image-drop-module";
// import { ImageUploader } from "quill-image-uploader";
// import ImageResize from "quill-image-resize-module";
import ImageResize from "quill-image-resize-module--fix-imports-error";

Quill.register("modules/imageDrop", ImageDrop);
Quill.register("modules/imageResize", ImageResize);
// Quill.register("modules/imageUploader", ImageUploader);

import { quillEditor } from "vue-quill-editor";
// import { quillRedefine } from "vue-quill-editor-upload"; //Import image upload

import Translations from "../mixins/Translations";
import CounterInput from "./CounterInput";
import RecipientForm from "./RecipientForm";
import EventForm from "./EventForm";
import FileSelect from "./FileSelect";
import PreviewModal from "./PreviewModal";
import RecipientItem from "./RecipientItem";

import StorageService from "../services/StorageService";

import { ToggleButton } from "vue-js-toggle-button";
import NebulaSenderService from "../services/NebulaSenderService";
import ApiService from "../services/ApiService";
export default {
  name: "MessageForm",
  mixins: [Translations],
  props: {
    existingMessage: Object,
  },
  components: {
    PreviewModal,
    CounterInput,
    RecipientForm,
    EventForm,
    FileSelect,
    quillEditor,
    ToggleButton,
    RecipientItem,
  },
  data() {
    return {
      upload: "",
      headers: {},
      loading: false,
      draftSaving: false,
      from: "",
      subject: "",
      sendToAll: false,
      useFileContent: false,
      gettingPreview: false,
      recipients: [],
      htmlFile: null,
      htmlContent: "",
      draftSaved: false,
      files: [],
      event: {
        createEvent: false,
        eventDetails: {
          eventTitle: "",
          eventDescription: "",
          eventFullDay: false,
          eventDateFrom: null,
          eventDateTo: null,
        },
      },
    };
  },
  beforeMount() {
    this.loading = true;
    // Sets draft content
    if (this.existingMessage && !_.isEmpty(this.existingMessage)) {
      this.from = this.existingMessage.from;
      this.subject = this.existingMessage.subject;
      this.sendToAll = this.existingMessage.send_to_all;
      this.recipients = this.existingMessage.recipients;
      this.htmlContent = this.existingMessage.content;
      this.draftSaved = true;
    }
  },
  mounted() {
    this.getToken();
  },
  computed: {
    /**
     * @return {Object}
     */
    config() {
      return StorageService.configuration;
    },
    /**
     * @return {boolean}
     */
    isThinking() {
      if (this.loading || this.gettingPreview || this.draftSaving) {
        return true;
      }

      return false;
    },
    /**
     * @return {Object}
     */
    quillConfiguration() {
      if (!StorageService.configuration.editor) {
        return {
          toolbar: [
            { header: 1 },
            { header: 2 },
            { header: 3 },
            { header: 4 },
            { list: "ordered" },
            { list: "bullet" },
            "bold",
            "italic",
            "link",
            "image",
          ],
        };
      }

      return StorageService.configuration.editor;
    },
    /**
     * @return {Object}
     */
    quillEditorOptions() {
      let rtrn = {
        modules: {
          ...this.quillConfiguration,

          imageResize: {
            modules: ["Resize", "DisplaySize"],
          },
          imageDrop: true,
        },
        theme: "snow",
        placeholder: this.messages["content-placeholder"],
      };
      return rtrn;
    },
    quillEditor() {
      return this.$refs.myQuillEditor.quill;
    },
    /**
     * @return {boolean}
     */
    nebulaSenderActive() {
      return NebulaSenderService.active;
    },
  },
  methods: {
    getToken() {
      Nova.request()
        .get("/token")
        .then((response) => {
          this.token = response.data;
          this.loading = false;
        })
        .catch((error) => {
          this.$toasted.show(error.response.data, { type: "error" });
          this.loading = false;
        });
    },
    /**
     * Has changed
     * @param  Object|undefined   newFile   Read only
     * @param  Object|undefined   oldFile   Read only
     * @return undefined
     */
    inputFile(newFile, oldFile) {
      let exists = false;
      for (let i = 0; i < this.files.length; i++) {
        const file = this.files[i];
        exists = Object.keys(file).some(function () {
          return file["name"] == newFile.name && file["id"] != newFile.id;
        });
        if (exists) break;
      }

      if (exists) {
        var removeIndex = this.files
          .map((file) => file.name)
          .indexOf(newFile.name);

        ~removeIndex && this.files.splice(removeIndex, 1);
      }

      newFile.headers.name = newFile.name;
      newFile.headers.type = newFile.type;
      // Automatic upload
      if (
        Boolean(newFile) !== Boolean(oldFile) ||
        oldFile.error !== newFile.error
      ) {
        if (!this.$refs.upload.active) {
          this.$refs.upload.active = true;
        }
      }
    },

    createEventChange(val) {
      this.event.createEvent = val;
    },

    changeEventData(val) {
      this.event.eventDetails = val;
    },

    /**
     * Pretreatment
     * @param  Object|undefined   newFile   Read and wriste
     * @param  Object|undefined   oldFile   Read only
     * @param  Function           prevent   Prevent changing
     * @return undefined
     */
    inputFilter: function (newFile, oldFile, prevent) {
      // Create a blob field
      newFile.blob = "";
      let URL = window.URL || window.webkitURL;
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file);
      }
    },

    removeFile(index) {
      this.files.splice(index, 1);
    },

    isImage(typeName) {
      if (!/\.(jpeg|jpe|jpg|gif|png|webp)$/i.test(typeName)) {
        return false;
      }
      return true;
    },

    /**
     * @name addAddress
     * @description Add recipient to the list
     * @param {Object} userObject
     */
    addAddress(userObject) {
      this.recipients.push(userObject);
    },

    /**
     * @name addGroup
     * @description Add a group of recipients to the list
     * @param {Array} userArray
     */
    addGroup(userArray) {
      for (const [key, value] of Object.entries(userArray)) {
        let newUser = { email: key, name: value };
        if (!this.recipients.some((recipient) => recipient["email"] === key)) {
          this.recipients.push(newUser);
        }
      }
    },

    /**
     * @name removeGroup
     * @description Remove a group of recipients from the list
     * @param {Array} userArray
     */
    removeGroup(userArray) {
      for (const [key, value] of Object.entries(userArray)) {
        if (this.recipients.some((recipient) => recipient["email"] === key)) {
          this.recipients = this.recipients.filter(function (recipient) {
            return recipient.email !== key;
          });
        }
      }
    },

    loadFile(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.$emit("load", e.target.result);
        this.htmlContent = e.target.result;
      };
      reader.readAsText(file);
    },

    /**
     * @name formIsValid
     * @description Is the form ready to be sent/submitted
     * @return {boolean}
     */
    formIsValid() {
      if (
        (this.subject && this.subject.length === 0) ||
        (this.htmlContent && this.htmlContent.length === 0) ||
        (this.event.createEvent &&
          ((!this.event.eventDetails.eventFullDay &&
            (this.event.eventDetails.eventDateFrom == null ||
              this.event.eventDetails.eventDateTo == null)) ||
            this.event.eventDetails.eventTitle.length === 0))
      ) {
        return false;
      }

      if (this.recipients && this.recipients.length === 0 && !this.sendToAll) {
        return false;
      }

      return true;
    },
    /**
     * @name draftIsValid
     * @description Is the form ready to be saved to a draft
     * @return {boolean}
     */
    draftIsValid() {
      return !(this.htmlContent.length === 0 || this.from.length === 0);
    },
    /**
     * @param {boolean} loading
     * @param {boolean} isDraft
     * @return {void}
     */
    setLoading(loading = true, isDraft = false) {
      if (!this.useFileContent) {
        this.quillEditor.enable(!loading);
      }
      if (isDraft) {
        this.draftSaving = loading;
      } else {
        this.loading = loading;
      }
    },

    /**
     * @name sendMessage
     * @description Sends the message with the defined
     * @return {void}
     */
    sendMessage() {
      let vm = this;

      vm.setLoading();

      // this.$refs.upload.active = true;

      ApiService.sendMessage(
        this.from,
        this.subject,
        this.sendToAll,
        this.recipients,
        this.htmlContent,
        this.files,
        this.event
      )
        .then((response) => {
          vm.$toasted.show(response, { type: "success" });
          vm.$emit("success");
          vm.setLoading(false);
        })
        .catch((error) => {
          let status = error.status;

          if (status === 422) {
            this.$toasted.show(error.data.message, {
              type: "error",
            });
          } else {
            this.$toasted.show(error.statusText, { type: "error" });
          }

          vm.setLoading(false);
        });
    },
    /**
     * @param {boolean} loading
     */
    setGettingPreview(loading = true) {
      if (!this.useFileContent) {
        this.quillEditor.enable(!loading);
      }
      this.gettingPreview = loading;
    },

    preview() {
      this.$refs.previewModal.preview(
        this.from,
        this.subject,
        this.recipients,
        this.htmlContent,
        this.sendToAll
      );
    },

    reset() {
      this.subject = "";
      this.sendToAll = false;
      this.complete = false;
      this.recipients = [];
      this.htmlContent = "";
      this.useFileContent = false;
    },

    /**
     * @name removeRecipient
     * @param {Number} index
     * @return {void}
     */
    removeRecipient(index) {
      this.recipients.splice(index, 1);
    },

    saveDraft() {
      this.setLoading(true, true);
      let template = StorageService.configuration.template.view;
      let request;

      if (this.existingMessage) {
        request = ApiService.updateDraft(
          this.existingMessage.id,
          this.htmlContent,
          this.from,
          this.existingMessage.template,
          this.subject,
          this.recipients,
          this.sendToAll
        );
      } else {
        request = ApiService.createDraft(
          this.from,
          template,
          this.htmlContent,
          this.subject,
          this.recipients,
          this.sendToAll
        );
      }

      request
        .then((response) => {
          this.$toasted.show(this.messages["draft-saved"], {
            type: "success",
          });

          if (this.existingMessage) {
            this.setLoading(false, true);
            this.$emit("update", response.data);
          } else {
            this.$router.push({
              name: "nebula-sender-drafts-edit",
              params: { id: response.data.id },
            });
          }
        })
        .catch((error) => {
          let status = error.status;

          if (status === 422) {
            this.$toasted.show(error.data.message, {
              type: "error",
            });
          } else {
            this.$toasted.show(error.statusText, { type: "error" });
          }
          this.setLoading(false, true);
        });
    },
  },
};
</script>

<style scoped>
ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
}
.attachment-item {
  position: relative;
  border: 1px solid rgba(0, 0, 0, 0.2);
  margin-top: 10px;
  padding-top: 10px;
  padding-bottom: 10px;
  padding-left: 25px;
  padding-right: 55px;
  width: 100%;
  height: 80px;
  overflow: hidden;
}
.attachment-item img {
  float: left;
  width: 80px;
  height: 100%;
  object-fit: contain;
}
.text-wrapper {
  overflow-wrap: break-word;
  text-overflow: ellipsis;
  line-height: 60px;
  padding-left: 1rem;
  padding-right: 1rem;
}
.file-upload-field {
  width: 100%;
  border: 2px solid lightsteelblue;
  margin-top: 1rem;
  padding: 0.5rem;
  font-size: 1.5rem;
}
.file-upload-field:hover {
  border: 2px solid lightblue;
}
.file-name {
  font-weight: bold;
}
.trash-box {
  position: absolute;
  right: 15px;
  top: 26px;
  filter: invert(40%) sepia(37%) saturate(4513%) hue-rotate(335deg)
    brightness(91%) contrast(96%);
}
.trash-box:hover {
  filter: invert(20%) sepia(61%) saturate(2458%) hue-rotate(340deg)
    brightness(104%) contrast(117%);
}
.upload-highlight {
  border: 1px dotted rgba(0, 0, 0, 0.3);
}
.drop-here-label {
  width: 83px;
  margin-left: auto;
  margin-right: auto;
  margin-top: 2rem;
  margin-bottom: 2rem;
}
.status {
  position: absolute;
  left: 9px;
  top: 28px;
}
.status.success:before {
  background-color: #94e185;
  border-color: #78d965;
  box-shadow: 0px 0px 4px 1px #94e185;
}
.status.fail:before {
  background-color: #c9404d;
  border-color: #c42c3b;
  box-shadow: 0px 0px 4px 1px #c9404d;
}
.status:before {
  content: " ";
  display: inline-block;
  width: 7px;
  height: 7px;
  margin-right: 10px;
  border: 1px solid #000;
  border-radius: 7px;
}
</style>
