<section id="config" title="Config">
  <div class="page-header">
    <h1>Config</h1>
  </div>

    <p class="lead">Expose documents via json rest.</p>

    <h2>Enable Shard Rest</h2>

    <p>The rest extenions needs to be configured with an endpoint map. The endpoint map defines which documents will be exposed by the rest controller, and what urls will be used to access them. Eg:</p>

<pre class="prettyprint linenums">
'zoop' => [
    'shard' => [
        'manifest' => [
            'default' => [
                'extension_configs' => [
                    'extension.rest' => [
                        'endpoint_map' => [
                            'users' => [
                                'class' => 'My\Users\Document',
                                'property' => 'username'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
]
</pre>

    <h2>Embedded Lists</h2>
    <p>To make embedded lists accessable via rest, configure them like this:</p>
<pre class="prettyprint linenums">
...
'endpoint_map' => [
    'users' => [
        'class' => 'My\Users\Document',
        'property' => 'username',
        'embedded_lists' => [
            'assets' => [
                'property' => 'name',
                'class' => 'My\Assets\Document'
            ]
        ]
    ]
]
...
</pre>

    <h2>Customize Route</h2>
    <p>Shard Module automatically configures the rest controller on the <code>/rest</code> route. You may wish to override that in your module config.</p>

    <p>By default, the config above would expose the user with username <code>toby</code> at:</p>
<pre class="prettyprint linenums">
http://myserver.com/rest/users/toby
</pre>

    <h2>Access Control</h2>
    <p>If the Access Control extension is enabled, all permissions will be respected.</p>

</section>
