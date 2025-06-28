// Quality of life changes to vanilla JS
const objectFilter = (object, callback) => {
	return Object.fromEntries(Object.entries(object).filter(([key, val]) => callback(val, key)));
};

const objectForEach = (object, callback) => {
	Object.entries(object).forEach(([key, val]) => callback(key, val));
};

const objectSort = (object, sortFunction = (a, b) => a - b) => {
	return Object.fromEntries(Object.entries(object).sort(([, a], [, b]) => sortFunction(a, b)));
};

HTMLCollection.prototype.forEach = Array.prototype.forEach;
DOMTokenList.prototype.filter = Array.prototype.filter;
NodeList.prototype.forEach = Array.prototype.forEach;
NodeList.prototype.filter = Array.prototype.filter;
NodeList.prototype.map = Array.prototype.map;
FileList.prototype.map = Array.prototype.map;

function areSameSet(A, B) {
	let n = A.length;

	if (B.length != n) return false;

	// Create a hash table to
	// number of instances
	let m = new Map();

	// for each element of A
	// increase it's instance by 1.
	for (let i = 0; i < n; i++) m.set(A[i], m.get(A[i]) == null ? 1 : m.get(A[i]) + 1);

	// for each element of B
	// decrease it's instance by 1.
	for (let i = 0; i < n; i++) m.set(B[i], m.get(B[i]) - 1);

	// Iterate through map and check if
	// any entry is non-zero
	for (let [key, value] of m.entries()) if (value != 0) return false;
	return true;
}

function hasJsonStructure(str) {
	if (typeof str === "object") return true;
	if (typeof str !== "string") return false;
	try {
		const result = JSON.parse(str);
		const type = Object.prototype.toString.call(result);
		return type === "[object Object]" || type === "[object Array]";
	} catch (err) {
		return false;
	}
}
