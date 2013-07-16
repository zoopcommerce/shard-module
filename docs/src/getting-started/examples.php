<section id="examples" title="Examples">
  <div class="page-header">
    <h1>Examples</h1>
  </div>

    <p class="lead">Simple shard configuration.</p>

    <h2>Turn on <a href="http://zoopcommerce.github.io/shard">Shard</a> Timestamping</h2>
    <p>Use the following in a module config:</p>
<pre class="prettyprint linenums">
'zoop' => [
    'shard' => [
        'manifest' => [
            'default' => [
                'extension_configs' => [
                    'extension.stamp' => true,
                ]
            ]
        ]
    ]
]
</pre>

    <h2>Use the Json Rest Controller</h2>
    <p>Get a list:</p>
<pre class="prettyprint linenums">
    http://myserver.com/rest/item
</pre>

    <p>Sort a list:</p>
<pre class="prettyprint linenums">
    http://myserver.com/rest/item?sort(+fieldname)
</pre>

</section>
