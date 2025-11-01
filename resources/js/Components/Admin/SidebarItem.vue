<template>
  <component
    :is="LinkTag"
    :href="href"
    class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors select-none"
    :class="isActive
      ? 'bg-indigo-50 text-indigo-700 font-medium border-l-4 border-indigo-500 pl-2'
      : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent pl-2'"
  >
    <!-- icon wrapper: color is driven by isActive so svg with stroke='currentColor' inherits it -->
    <div
      :class="['w-5 h-5 flex items-center justify-center shrink-0', isActive ? 'text-indigo-600' : 'text-gray-500']"
      v-html="icon"
      aria-hidden="true"
    ></div>

    <span class="truncate leading-5">{{ label }}</span>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/inertia-vue3'

const props = defineProps({
  label: { type: String, required: true },
  icon: { type: String, default: '' }, // should be SVG string using stroke="currentColor"
  href: { type: String, required: true },
  exact: { type: Boolean, default: false },
})

const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/'
const isActive = computed(() => {
  if (!props.href) return false
  try {
    const url = new URL(props.href, window.location.origin)
    const path = url.pathname
    return props.exact ? path === currentPath : currentPath.startsWith(path)
  } catch (e) {
    return props.exact ? props.href === currentPath : currentPath.startsWith(props.href)
  }
})

const LinkTag = Link
</script>

<style scoped>
/* ensure svg inside v-html wrapper sizes and stroke weight consistently */
.w-5 > svg {
  width: 18px;
  height: 18px;
  display: block;
}

/* force svg lines to pick currentColor */
.w-5 > svg [stroke] { stroke: currentColor !important; }
.w-5 > svg [fill="none"] { fill: none !important; }
</style>
