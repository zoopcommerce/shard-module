<section id="get-list" title="Get List">
  <div class="page-header">
    <h1>Get List</h1>
  </div>

    <p class="lead">Get a list of documents.</p>

    <p>An array of documents will be returned when a list is requested, rather than a specific document. A successful response will include a <code>contentRange: x-x/x</code> header.</p>

<pre class="prettyprint linenums">
http://myserver.com/rest/user
</pre>

    <h2>Partial documents</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?select(username,age)
</pre>

    <h2>Filter</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?country=australia
</pre>

    <h2>And Filter</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?country=australia&type=active
</pre>

    <h2>Or Filter</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?country=[australia, usa]
</pre>

    <h2>Sort Ascending</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?sort(+username)
</pre>

    <h2>Sort Decending</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?sort(-username)
</pre>

    <h2>Multiple Sort</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/user?sort(+country,+username)
</pre>

    <h2>Offset</h2>
<pre class="prettyprint linenums">
HEADERS
range: items=2-25
http://myserver.com/rest/user
</pre>

    <h2>Nested List</h2>
<pre class="prettyprint linenums">
http://myserver.com/rest/assets
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
    <td>If the endpoint requested does not exist.</td>
</tr>
<tr>
    <td>416</td>
    <td><code>Content-Type: application/api-problem+json</code>, Requested range cannot be returned. Occurs if a bad range is requested, eg: <code>Range: 10-5</code></td>
</tr>
<tr>
    <td>204</td>
    <td>If the requested list is empty.</td>
</tr>
</tbody>
</table>

</section>
