<template>
  <div>
    <input type="text" :name="name" :class="inputClass" :id="name" v-model="inputModel" v-mask="'###.###.###-##'"
      :required="required">
    <span v-if="errorMessage" class="help-block">
      <strong>{{ errorMessage }}</strong>
    </span>
  </div>
</template>

<script>
export default {
  props: ['name', 'value', 'required', 'class_name', 'error'],
  data() {
    return {
      inputModel: ''
    }
  },
  computed: {
    errorMessage() {
      return this.error ? this.error : '';
    },
    inputClass() {
      return this.class_name + (this.error ? ' has-error' : '');
    }
  },
  mounted() {
    this.inputModel = this.value;
  }
}
</script>

<style scoped>
.help-block {
  color: rgba(100, 90, 90, 0.74);
}
</style>
