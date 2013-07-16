<section id="get" title="Get">
  <div class="page-header">
    <h1>Get</h1>
  </div>

    <p class="lead">Get a single document.</p>

    <p>To get a document use:</p>

<pre class="prettyprint linenums">
http://myserver.com/rest/user/toby
</pre>

    <h2>Partial documents</h2>

    <p>To get only some properties of a document use:</p>
<pre class="prettyprint linenums">
http://myserver.com/rest/user/toby?select(username,age)
</pre>

    <h2>Single nested document</h2>

    <p>Append the property name:</p>
<pre class="prettyprint linenums">
http://myserver.com/rest/user/toby/address
</pre>

    <h2>Single document from a nested list</h2>

    <p>To get one item from a list of documents append the property name and the id of the document you want:</p>
<pre class="prettyprint linenums">
http://myserver.com/rest/user/toby/assets/funky-car
</pre>

    <h2>Errors</h2>

<table class="table table-bordered table-striped">
  <thead>
   <tr>
     <th style="width: 100px;">error</th>
     <th>description</th>
   </tr>
  </thead>
  <tbody>
<tr>
    <td>404</td>
    <td>If the endpoint requested does not exists, or the document requested cannot be found.</td>
</tr>
</tbody>
</table>

</section>
