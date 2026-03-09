export default function mergeMessages(mergedMessages) {
    // remove duplicates:
    const uniqueMessages = [...mergedMessages.reduce((p, c) => p.set(c.id, c), new Map())].map(([key, value]) => value);

    // sort:
    return uniqueMessages.sort((a, b) => {
        if (a.sortKey < b.sortKey) {
            return -1;
        }
        if (a.sortKey > b.sortKey) {
            return 1;
        }

        return 0;
    });
}