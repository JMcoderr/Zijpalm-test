{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}

<div
    class="z-10 fixed inset-0 bg-black/10 hidden [[data-show-stashed-sidebar]_&]:block lg:[[data-show-stashed-sidebar]_&]:hidden"
    x-data x-on:click="document.body.removeAttribute('data-show-stashed-sidebar')"
></div>
