<template>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div class="min-w-0">
        <slot name="left" />
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <slot name="right" />
      </div>
    </div>
  </template>
  