<template>
  <form @submit="saveFormSubmitSettings">
    <div>
      <h2 class="mb-2 text-base font-bold">General</h2>

      <div>
        <div class="mb-4">
          <D9Label label="Headline" />
          <D9Input type="text" block v-model="outroHeadline" />
        </div>
        <div class="mb-4">
          <D9Label label="Message" />
          <D9Input type="text" block v-model="outroMessage" />
        </div>
      </div>
    </div>

    <div class="mt-8">
      <h2 class="mb-2 flex items-center text-base font-bold">
        <span class="inline-block">CTA Link</span>
        <D9Switch class="ml-2" label="" v-model="isCtaOn"></D9Switch>
      </h2>

      <div
        class="origin-top transition-all duration-200"
        :class="
          isCtaOn
            ? 'opacity-100'
            : 'pointer-events-none h-0 scale-y-0 opacity-0 blur-sm'
        "
      >
        <div class="mb-4">
          <D9Label label="Label" />
          <D9Input type="text" block :disabled="!isCtaOn" v-model="ctaLabel" />
        </div>
        <div class="mb-4">
          <D9Label label="Link" />
          <D9Input type="url" block :disabled="!isCtaOn" v-model="ctaLink" />
        </div>
        <div class="mb-4 flex justify-between">
          <D9Label label="Append Incoming Query Parameters" />
          <D9Switch
            label="Append Incoming Query Parameters"
            v-model="ctaAppendParams"
            onLabel="yes"
            offLabel="no"
          />
        </div>
      </div>
    </div>

    <div class="mt-8">
      <h2 class="mb-2 flex items-center text-base font-bold">
        <span class="inline-block">Social Links</span>
        <D9Switch
          class="ml-2"
          label="Social Links"
          v-model="isSocialOn"
        ></D9Switch>
      </h2>

      <div
        class="origin-top transition-all duration-200"
        :class="
          isSocialOn
            ? 'opacity-100'
            : 'pointer-events-none h-0 scale-y-0 opacity-0 blur-sm'
        "
      >
        <div class="mb-4">
          <D9Label label="Instagram" />
          <D9Input
            type="url"
            block
            :disabled="!isSocialOn"
            v-model="instagram"
          />
        </div>
        <div class="mb-4">
          <D9Label label="Facebook" />
          <D9Input
            type="url"
            block
            :disabled="!isSocialOn"
            v-model="facebook"
          />
        </div>
        <div class="mb-4">
          <D9Label label="Twitter" />
          <D9Input type="url" block :disabled="!isSocialOn" v-model="twitter" />
        </div>
        <div class="mb-4">
          <D9Label label="LinkedIn" />
          <D9Input
            type="url"
            block
            :disabled="!isSocialOn"
            v-model="linkedin"
          />
        </div>
        <div class="mb-4">
          <D9Label label="Github" />
          <D9Input type="url" block :disabled="!isSocialOn" v-model="github" />
        </div>
      </div>
    </div>

    <D9Button
      class="mt-8"
      type="submit"
      label="Save Completion Page"
      :is-loading="isSaving"
    />
  </form>
</template>

<script setup lang="ts">
import { useForm } from "@/stores";
import { D9Label, D9Input, D9Switch, D9Button } from "@deck9/ui";
import { ref } from "vue";

const store = useForm();
const isSaving = ref(false);

const outroHeadline = ref(store.form?.eoc_headline);
const outroMessage = ref(store.form?.eoc_text);

const isCtaOn = ref(store.form?.show_cta_link ? true : false);
const ctaLabel = ref(store.form?.cta_label);
const ctaLink = ref(store.form?.cta_link);
const ctaAppendParams = ref(store.form?.cta_append_params);

const isSocialOn = ref(store.form?.show_social_links ? true : false);
const instagram = ref(store?.form?.instagram);
const github = ref(store?.form?.github);
const linkedin = ref(store?.form?.linkedin);
const facebook = ref(store?.form?.facebook);
const twitter = ref(store?.form?.twitter);

const saveFormSubmitSettings = async () => {
  isSaving.value = true;
  try {
    await store.updateForm({
      eoc_headline: outroHeadline.value,
      eoc_text: outroMessage.value,

      show_cta_link: isCtaOn.value,
      cta_label: ctaLabel.value,
      cta_link: ctaLink.value,
      cta_append_params: ctaAppendParams.value,

      show_social_links: isSocialOn.value,
      instagram: instagram.value,
      github: github.value,
      linkedin: linkedin.value,
      facebook: facebook.value,
      twitter: twitter.value,
    });
  } catch (e) {
    console.error(e);
  } finally {
    isSaving.value = false;
  }
};
</script>