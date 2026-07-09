import 'package:flutter/material.dart';
import '../models/item_model.dart';

class ItemProvider extends ChangeNotifier {
  final List<Item> _items = [];

  List<Item> get items => _items;

  void addItem(Item item) {
    _items.add(item);
    notifyListeners();
  }

  void removeItem(int index) {
    _items.removeAt(index);
    notifyListeners();
  }

  void updateItem(int index, Item item) {
    _items[index] = item;
    notifyListeners();
  }

  Item getItem(int index) {
    return _items[index];
  }

  void clearItems() {
    _items.clear();
    notifyListeners();
  }
}