<section id="put" title="Put">
  <div class="page-header">
    <h1>Put</h1>
  </div>

    <p class="lead">Update a document.</p>

    <p>Note: PUT will update <i>all</i> properties of a document. If you need to update only some of the properties of a doument, use PATCH.</p>

    <p>The response from a successful PUT will always be a 204.</p>

    <h2>Update a document</h2>

<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
{"age": "18"}
http://myserver.com/rest/user/toby
</pre>

    <h2>Create via PUT</h2>

    <p>If the document selected for update does not exist, it will be created.</p>
<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
{"age": "27"}
http://myserver.com/rest/user/lucy
</pre>

    <h2>Update single nested document</h2>

    <p>Append the property name:</p>
<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
{"street": "Street Rd", "number": "45", city: "Sydney"}
http://myserver.com/rest/user/toby/address
</pre>

    <h2>Update single document in a nested list</h2>

    <p>To update one item from a list of documents append the property name and the id of the document you want:</p>
<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
{"make": "gm", "model": "camero"}
http://myserver.com/rest/user/toby/assets/funky-car
</pre>

    <h2>Replace a whole list</h2>

<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
[
   {"username": "gumpy"},
   {"username": "sleepy"}
   {"username": "happy"}
]
http://myserver.com/rest/user
</pre>

    <h2>Update document id</h2>

    <p>A document id can be updated. To do so, include the new document id in the PUT data. Note that this will actually delete the existing document, and create a new document with the new id.</p>
<pre class="prettyprint linenums">
PUT
HEADERS
Content-type: application/json

CONTENT
{"username": "toby-different"}
http://myserver.com/rest/user/toby
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
    <td>If the endpoint or document requested does not exist.</td>
</tr>
<tr>
    <td>500</td>
    <td><code>Content-Type: application/api-problem+json</code>, Document validation failed. Occurs if the validation extension is turned on, and validation fails.</td>
</tr>
</tbody>
</table>

</section>
