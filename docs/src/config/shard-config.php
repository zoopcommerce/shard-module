<section id="shard-config" title="Shard Config">
  <div class="page-header">
    <h1>Shard Config</h1>
  </div>

    <p class="lead">Specify which Shard Extensions you want to use.</p>

    <p>Shard Module supports multiple DocumentManager-Manifest pairs. Each manifest needs to be configured with a document manager, and the extensions you want to use. Eg:</p>

<pre class="prettyprint linenums">
'zoop' => [
    'shard' => [
        'manifest' => [ //Array of different manifests. (Most of the time you'll only use one)
            'default' => [ //The manifest name
                ... //Put the manifest config in here
            ]
        ]
    ]
]
</pre>

    <p>Note, that the value of <code>document_manager</code> in the manifest config can be the name of the document manager service configured by DoctrineMongoODMModule.</p>

    <p>Example complete config (with three extensions turned on):</p>

<pre class="prettyprint linenums">
'zoop' => [
    'shard' => [
        'manifest' => [
            'default' => [
                'document_manager' => 'doctrine.odm.documentmanager.default',
                'extension_configs' => [
                        'extension.accessControl' => true,
                        'extension.freeze' => true,
                        'extension.owner' => true,
                ],
            ]
        ]
    ],
]
</pre>

    <p>All other config keys in a manifest configuration are supported. See <a href="http://zoopcommerce.github.io/shard/config.html#manual-config">shard docs</a>.</p>

    <h2>User Config</h2>

    <p>Shard Module will automatically configure a shard manifest to use any object returned by <code>$serviceLocator->get(Zend\Authentication\AuthenticationService)->getIdentity()</code> as the user.</p>

    <p>If you would like to use zend authentication integrated with Doctrine and Shard's access control, then take a look at <a href="http://zoopcommerce.github.io/gateway-module">Gateway Module</a>.</p>
</section>
