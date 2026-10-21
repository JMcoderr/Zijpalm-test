@props(['product'])

<div data-shop-description class="whitespace-normal text-left wrap-anywhere [&_p]:m-0 [&_p:empty]:min-h-[1lh] [&_a]:underline [&_a]:font-bold">{!! $product->descriptionHtml() !!}</div>