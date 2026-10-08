// A failed refresh must not hide the error returned by the original action.
export async function withRefresh(action, refresh) {
    let failure
    try {
        await action()
    } catch (error) {
        failure = error
    }
    try {
        await refresh()
    } catch (error) {
        if (!failure) failure = error
    }
    if (failure) throw failure
}
