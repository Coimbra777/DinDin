<template>
  <div>
    Oi
    <!-- <div v-if="width >= 768">
      <div class="first-section__banner spa-section">
        <carousel
          ref="carouselSelector"
          :navigationEnabled="false"
          :pagination-enabled="false"
          :loop="true"
          :autoplay="true"
          :perPage="1"
          :autoplayTimeout="2000"
          :pauseOnOver="true"
        >
          <slide v-for="(item, index) in items" class="spa-section" :key="index">
            <div
              :style="
                'background-image:linear-gradient(180deg, rgba(0, 0, 0, 0) 43.23%, rgba(0, 0, 0, 0.72) 100%), url(' +
                item.banner +
                ');'
              "
              class="oi-sou-o-items ui-image--cover first-section__banner-item"
            ></div>
          </slide>
        </carousel>
      </div>
    </div>
    <div v-if="width < 768">
      <div class="first-section__banner spa-section">
        <carousel
          ref="carouselSelector"
          :navigationEnabled="false"
          :pagination-enabled="false"
          :loop="true"
          :autoplay="true"
          :perPage="1"
          :autoplayTimeout="2000"
        >
          <slide v-for="(item, index2) in items" class="spa-section" :key="index2">
            <div
              :style="
                'background-image:linear-gradient(180deg, rgba(0, 0, 0, 0) 43.23%, rgba(0, 0, 0, 0.72) 100%), url(' +
                item.banner_mobile +
                ');'
              "
              class="ui-image--cover first-section__banner-item"
            ></div>
          </slide>
        </carousel>
      </div>
    </div> -->
  </div>
</template>

<script>
import { Carousel, Slide } from 'vue-carousel'
export default {
  props: ['items'],
  data() {
    return {
      width: screen.width,
    }
  },
  components: {
    Carousel,
    Slide,
  },
  methods: {
    navigateTo(index) {
      this.slider = index
      this.$refs.carouselSelector.goToPage(index)
    },
  },
  mounted() {
    // setTimeout(() => {
    //   this.$refs['carouselSelector'].onResize()
    //   this.$refs['carouselSelector'].goToPage(0)
    // }, 200)
  },
}
</script>

<style lang="scss">
</style>
