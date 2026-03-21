<template>
  <div>
    <v-card class="rounded-lg" elevation="1" :dark="$vuetify.theme.dark">
      <v-card-title class="subtitle-1 font-weight-bold py-4 flex-wrap">
        <v-icon left color="primary">mdi-account-multiple-outline</v-icon>
        Utilizadores
        <v-spacer />
        <v-text-field
          v-model="searchQ"
          dense
          outlined
          hide-details
          clearable
          placeholder="Nome ou email"
          prepend-inner-icon="mdi-magnify"
          class="admin-search pt-0 mt-0"
          style="max-width: 280px"
          @keyup.enter="reloadFirstPage"
          @click:clear="reloadFirstPage"
        />
        <v-btn color="primary" class="ml-2 mt-2 mt-sm-0" depressed rounded @click="reloadFirstPage">
          Filtrar
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :server-items-length="total"
        :options.sync="options"
        :footer-props="{
          'items-per-page-options': [10, 15, 25, 50],
        }"
        class="admin-user-table"
        @update:options="fetchUsers"
      >
        <template slot="item.actions" slot-scope="{ item }">
          <v-btn small color="primary" outlined rounded @click="$emit('edit', item.id)">
            <v-icon left small>mdi-pencil</v-icon>
            Editar
          </v-btn>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'UserList',
  props: {
    apiBase: { type: String, required: true },
  },
  data() {
    return {
      headers: [
        { text: 'Nome', value: 'name', sortable: false },
        { text: 'Email', value: 'email', sortable: false },
        { text: '', value: 'actions', sortable: false, align: 'end', width: '140px' },
      ],
      items: [],
      total: 0,
      loading: false,
      searchQ: '',
      options: {
        page: 1,
        itemsPerPage: 15,
        sortBy: [],
        sortDesc: [],
      },
    }
  },
  methods: {
    reloadFirstPage() {
      this.options = { ...this.options, page: 1 }
      this.fetchUsers()
    },
    async fetchUsers() {
      const opt = this.options || {}
      const page = opt.page || 1
      const perPage = opt.itemsPerPage || 15
      this.loading = true
      try {
        const { data } = await axios.get(`${this.apiBase}/users`, {
          params: {
            page,
            per_page: perPage,
            q: this.searchQ || undefined,
          },
        })
        this.items = data.data || []
        this.total = data.total || 0
      } catch (e) {
        const msg =
          (e.response && e.response.data && e.response.data.message) ||
          'Não foi possível carregar utilizadores.'
        this.$emit('error', msg)
        this.items = []
        this.total = 0
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
@media (min-width: 600px) {
  .admin-search {
    margin-top: 0 !important;
  }
}
</style>
