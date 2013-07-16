<section id="using-services" title="Using Services">
  <div class="page-header">
    <h1>Using Services</h1>
  </div>

    <p class="lead">Use services provied by Shard Extensions.</p>

    <p>Many Shard extensions provide services. To access those services through the main application service manager, just prefix the service name with <code>shard.$manifestName.</code>. Eg:</p>

<pre class="prettyprint linenums">
$serializer = $serviceLocator->get('shard.default.serializer');
//and
$accessController = $serviceLocator->get('shard.default.accessController');
</pre>

</section>
