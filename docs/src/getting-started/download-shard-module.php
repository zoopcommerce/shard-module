<section id="download-shard-module" title="Install">
  <div class="page-header">
    <h1>Install</h1>
  </div>

    <p class="lead">A zf2 module ready to go.</p>

    <p>Shard Module requires php 5.4</p>

    <h2>Get source</h2>

    <div class="row-fluid">

      <div class="span6">
        <h2>Source with Composer</h2>
        <p>Get the source, dependencies, and easily manage versioning. This is the recommended way to install.</p>
        <p>Add the following to your root <code>composer.json</code>:</p>
<pre class="prettyprint linenums">
require: [
    "zoopcommerce/shard-module": "~1.0"
]
</pre>
      </div>

      <div class="span6">
        <h2 class="muted">Source from Github</h2>
        <p>Once downloaded, you'll need to run composer in Shard Module's root directory to install dependencies.</p>
        <p><a class="btn btn-large" href="https://github.com/zoopcommerce/shard-module/zipball/master" ><span class="muted">Download Shard Module source</span></a></p>
        <p>or gittish people:</p>
<pre class="prettyprint linenums">
git clone http://github.com/zoopcommerce/shard-module
</pre>
      </div>
    </div>

    <h2>Add to applicaiton.config.php</h2>

    <p>Add the following four modules to your zf2 application config (order is significant):</p>

<pre class="prettyprint linenums">
'modules' => [
    'Zoop\MaggottModule',
    'DoctrineModule',
    'DoctrineMongoODMModule',
    'Zoop\ShardModule'
]
</pre>

    <p>Each of these modules does the following:</p>

<table class="table table-bordered table-striped">
  <thead>
   <tr>
     <th style="width: 100px;">module</th>
     <th>description</th>
   </tr>
  </thead>
  <tbody>
<tr>
    <td>Zoop\MaggottModule</td>
    <td>An exception handling module for zf2. In paricular handles rendering exceptions as json. See <a href="http://zoopcommerce.github.io/maggott-module">here</a> for more details.</td>
</tr>
<tr>
    <td>DoctrineModule</td>
    <td>The official core module for integrating Doctrine with zf2. See <a href="http://github.com/doctinre/DoctrineModule">here.</a></td>
</tr>
<tr>
    <td>DoctrineMongoODMModule</td>
    <td>The official module for integrating Doctrine Mongo ODM with zf2. See <a href="http://github.com/doctrine/DoctrineMongoODMModule">here</a></td>
</tr>
<tr>
    <td>Zoop\ShardModule</td>
    <td>This.</td>
</tr>
</tbody>
</table>

</section>
