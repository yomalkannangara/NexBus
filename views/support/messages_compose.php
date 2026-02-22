<div class="container">
  <h2>Compose Message</h2>
  <form method="post">
    <div class="mb-3">
      <label>Subject</label>
      <input name="subject" class="form-control" />
    </div>
    <div class="mb-3">
      <label>Body</label>
      <textarea name="body" class="form-control" rows="6"></textarea>
    </div>
    <div class="mb-3">
      <label>Recipients (comma-separated user IDs)</label>
      <input name="recipients" class="form-control" placeholder="31,32" />
    </div>
    <div class="mb-3">
      <label>Scope</label>
      <select name="scope" class="form-control">
        <option value="user">User</option>
        <option value="depot">Depot</option>
        <option value="route">Route</option>
        <option value="bus">Bus</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Scope value (e.g. depot id, route id)</label>
      <input name="scope_value" class="form-control" />
    </div>
    <button class="btn btn-primary">Send</button>
  </form>
</div>
