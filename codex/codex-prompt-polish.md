Read `codex.md` first.

Task:

Why you have this function ?

private function queryForTenant(): Builder
{
return DesignRequest::query()
->where('tenant_id', $this->tenantId());
}

we already have BelongsToTenant on our models. You don't need that because we have sprout package that separates the tenant form other tenants.
