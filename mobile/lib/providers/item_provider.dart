import 'package:flutter/material.dart';
import '../models/item_model.dart';

class ItemProvider extends ChangeNotifier {
  final List _items = [];

  List get items => _items;

  void addItem(ItemModel item) {
    _items.add(item);
    notifyListeners();
  }

  void removeItem(int index) {
    _items.removeAt(index);
    notifyListeners();
  }

  void updateItem(int index, ItemModel item) {
    _items[index] = item;
    notifyListeners();
  }

  ItemModel getItem(int index) {
    return _items[index];
  }

  void clearItems() {
    _items.clear();
    notifyListeners();
  }
}